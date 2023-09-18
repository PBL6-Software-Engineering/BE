<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestChangePassword;
use App\Http\Requests\RequestCreatePassword;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use SebastianBergmann\Environment\Console;
use Exception;
use Mail;        
use Illuminate\Support\Facades\DB;
use App\Mail\SendPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Http\Requests\RequestCreateUser;
use App\Http\Requests\RequestLogin;
use App\Http\Requests\RequestUpdateInfor;
use App\Http\Requests\RequestUpdateUser;
use App\Jobs\SendForgotPasswordEmail;
use App\Models\PasswordReset;
use App\Rules\ReCaptcha;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class UserController extends Controller
{

    public function login(Request $request)
    {
        
        $u = User::where('email',$request->email)->first();
        if(empty($u)){
            return response()->json(['error' => 'Email is incorrect !'], 401);
        }
        else {
            $status = $u->status;
            if($status == 0){
                return response()->json(['error' => 'Your account has been locked !'], 401);
            } 
        }

        $credentials = request(['email', 'password']);
        $user = User::where('email',$request->email)->first();
        if (!$token = auth()->guard('user_api')->attempt($credentials)) {
            return response()->json(['error' => 'Either email or password is wrong. !'], 401);
        }

        return response()->json([
            'user' => $user,
            'message'=>$this->respondWithToken($token)
        ]);
    }

    public function saveAvatar(Request $request){
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $filename =  pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/avatars', $filename);
            return 'storage/image/avatars/' . $filename;
        }
    }

    public function register(RequestCreateUser $request)
    {
        $userEmail = User::where('email', $request->email)->first();
        if($userEmail){
            if($userEmail['password']){
                return response()->json(['error' => 'Account already exists !'], 401);
            }
            else { 
                $avatar = $this->saveAvatar($request);
                $userEmail->update(array_merge(
                    $request->all(),
                    ['password' => Hash::make($request->password),'status'=> "1",'avatar' => $avatar]
                ));
                return response()->json([
                    'message' => 'User successfully registered',
                    'user' => $userEmail
                ], 201);
            }
        }
        else {
            $avatar = $this->saveAvatar($request);
            $user = User::create(array_merge(
                $request->all(),
                ['password' => Hash::make($request->password),'status'=> "1", 'avatar' => $avatar]
            ));
            return response()->json([
                'message' => 'User successfully registered',
                'user' => $user
            ], 201);
        }
    }

    /**
     * redirectToGoogle
     *
     * @return object
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * handleGoogleCallback
     *
     * @return object
     */
    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->stateless()->user();
            $ggUser = User::where('google_id',$user->id)->first();
            if ($ggUser) {
                if ($ggUser->status == 0) {
                    return response()->json(['error' => 'Your account has been locked or not approved !'], 401);
                } else {
                    Auth::login($ggUser);
                    $this->token = auth()->guard('user_api')->login($ggUser);
                    $ggUser->access_token = $this->respondWithToken($this->token)->getData()->access_token;
                    return response()->json([
                        'message' => 'Login by Google successfully !',
                        'user' => $ggUser,
                    ], 201);
                }
            } else {
                $findEmail = User::where('email',$user->email)->first();
                if ($findEmail) {
                    $findEmail->update([
                        'google_id' => $user->id
                    ]);
                    return response()->json([
                        'message' => 'Login by Google successfully !',
                        'user' => $findEmail
                    ], 201);
                } else {
                    $newUser = User::create([
                        'name' => $user->name,
                        'email' => $user->email,
                        'google_id' => $user->id,
                        'username' => 'user_' . $user->id,
                        'avatar' => $user->avatar,
                        'status' => 1
                    ]);
                    $user = User::find($newUser->id);
                }
                return response()->json([
                    'message' => 'User successfully registered',
                    'user' => $user
                ], 201);
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e], 401);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        return response()->json(auth('user_api')->user());
    }

    public function updateProfile(RequestUpdateUser $request, $id_user)
    {
        $user = User::find($id_user);
        if($request->hasFile('avatar')) {
            if (!Str::startsWith($user->avatar, 'http')) {
                if ($user->avatar) {
                    File::delete($user->avatar);
                }
            }
        }
        $avatar = $this->saveAvatar($request);
        $user->update(array_merge($request->all(),['avatar' => $avatar]));
        return response()->json([
            'message' => 'User successfully updated',
            'user' => $user
        ], 201);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('user_api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('user_api')->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->guard('user_api')->factory()->getTTL() * 60
        ]);
    }
    
    public function changePassword(RequestChangePassword $request) {
        $user = User::find($request->id);
        if (!(Hash::check($request->get('current_password'), $user->password))) {
            return response()->json([
                'message' => 'Your current password does not matches with the password.',
            ],400);
        }
        $user->update(['password' => Hash::make($request->get('new_password'))]);
        return response()->json([
            'message' => "Password successfully changed !",
        ],200);
    }

    public function createPassword(RequestCreatePassword $request) {
        $user = User::find($request->id);
        $user->update(['password' => Hash::make($request->get('new_password'))]);
        return response()->json([
            'message' => "Password successfully changed ! ",
        ],200);
    }

    /**
     * forgotForm
     *
     * @return view
     */
    public function forgotForm(Request $request)
    {
        return view('blog.auth.reset_password');
    }
    
    /**
     * forgotSend
     *
     * @param Request $request
     * @return object
     */
    public function forgotSend(Request $request)
    {
        try {
            $email = $request->email;
            $token = Str::random(32);
            $is_user = 1;
            $user = PasswordReset::where('email',$email)->where('is_user', $is_user)->first();
            if ($user) {
                $user->update(['token' => $token]);
            } else {
                PasswordReset::create([
                    'email' => $email,
                    'token' => $token,
                    'is_user' => $is_user
                ]);
            }
            $url = 'http://localhost:8080/forgot-form?token=' . $token;
            Log::info("Add jobs to Queue , Email: $email with URL: $url");
            Queue::push(new SendForgotPasswordEmail($email, $url));
            return response()->json([
                'message' => "Send Mail Password Reset Success !",
            ],200);
        } catch (\Exception $e) {
            Log::error('Error occurred: ' . $e->getMessage());

            return response()->json([
                'error' => 'An error occurred while sending the reset email.'
            ], 500);
        }
    }

        /**
     * forgotUpdate
     *
     * @param object $filter
     */
    public function forgotUpdate(RequestCreatePassword $request)
    {
        try {
            $new_password = Hash::make($request->new_password);
            $userReset = PasswordReset::where('token',$request->token)->first();
            if ($userReset) {
                $user = User::where('email',$userReset->email)->first();
                if ($user) {
                    $user->update(['password' => $new_password]);
                    $userReset->delete();
                    return response()->json([
                        'message' => "Password Reset Success !",
                    ],200);
                }
                return response()->json([
                    'message' => "Can not find the account !",
                ],401);
            } else {
                return response()->json([
                    'message' => "Token has expired !",
                ],401);
            }
        } catch (\Exception $e) {
        }
    }
}
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
use App\Models\InforUser;
use App\Models\PasswordReset;
use App\Rules\ReCaptcha;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{


    public function profile()
    {
        $user = User::find(auth('user_api')->user()->id);
        $inforUser = InforUser::where('id_user', $user->id)->first();

        return response()->json([
            'user' => array_merge($user->toArray(), $inforUser->toArray()),
        ]);
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
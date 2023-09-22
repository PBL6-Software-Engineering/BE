<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestChangePassword;
use App\Http\Requests\RequestCreateInforUser;
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

class InforUserController extends Controller
{

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

    public function saveAvatar(Request $request){
        // $pathToFile = $request->file('avatar')->store('image/avatars','public');
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $filename =  pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_user_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/avatars/users', $filename);
            return 'storage/image/avatars/users' . $filename;
            // public/image/avatars/users : giả sử folder này chưa có thì nó tự động tạo folder này luôn . Mình không cần tự tạo . 
        }
    }

    public function register(RequestCreateInforUser $request)
    {
        $userEmail = User::where('email', $request->email)->where('role', 'user')->first();
        if($userEmail){
            if($userEmail['password']){
                return response()->json(['error' => 'Account already exists !'], 401);
            }
            else { 
                $avatar = $this->saveAvatar($request);
                $userEmail->update(array_merge(
                    $request->all(),
                    ['password' => Hash::make($request->password),'avatar' => $avatar]
                ));

                $inforUser = InforUser::where('id_user',$userEmail->id)->first();
                $inforUser->update(array_merge(
                    $request->all()
                ));
                
                return response()->json([
                    'message' => 'User successfully registered',
                    'user' => array_merge($userEmail,$inforUser),
                ], 201);
            }
        }
        else {
            $avatar = $this->saveAvatar($request);
            $user = User::create(array_merge(
                $request->all(),
                ['password' => Hash::make($request->password), 'is_accept'=> 0, 'role'=> 'user', 'avatar' => $avatar]
            ));

            $inforUser = InforUser::create(array_merge(
                $request->all(),
                ['id_user' => $user->id]
            ));

            return response()->json([
                'message' => 'User successfully registered',
                'user' => array_merge($user->toArray(), $inforUser->toArray()),
            ], 201);
        }
    }


    // Login by Google User 
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->stateless()->user();
            $ggUser = User::where('google_id',$user->id)->first();
            if ($ggUser) {
                if ($ggUser->is_accept == 0) {
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
                        'is_accept' => 1
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
    // Login by Google User 


    public function test_midle(){

        return response()->json(['test' => 'test role'.auth('user_api')->user()->role], 200);
    }

    public function login(Request $request)
    {
        
        $u = User::where('email',$request->email)->first();
        if(empty($u)){
            return response()->json(['error' => 'Email is incorrect !'], 401);
        }
        else {
            $is_accept = $u->is_accept;
            if($is_accept == 0){
                return response()->json(['error' => 'Your account has been locked !'], 401);
            } 
        }

        $credentials = request(['email', 'password']);
        $user = User::where('email',$request->email)->first();
        if (!$token = auth()->guard('user_api')->attempt($credentials)) {
            return response()->json(['error' => 'Either email or password is wrong. !'], 401);
        }

        $inforUser = InforUser::where('id_user', $user->id)->first();

        return response()->json([
            'user' => array_merge($user->toArray(), $inforUser->toArray()),
            'message'=>$this->respondWithToken($token)
        ]);
    }


}

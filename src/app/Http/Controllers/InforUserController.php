<?php

namespace App\Http\Controllers;

use App\Enums\UserEnum;
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
use App\Jobs\SendMailNotify;
use App\Jobs\SendVerifyEmail;
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
            $image->storeAs('public/image/avatars/users/', $filename);
            return 'storage/image/avatars/users/' . $filename;
            // public/image/avatars/users : giả sử folder này chưa có thì nó tự động tạo folder này luôn . Mình không cần tự tạo . 
        }
    }

    public function register(RequestCreateInforUser $request)
    {
        $userEmail = User::where('email', $request->email)->where('role', 'user')->first();
        if($userEmail){
            if($userEmail['password']){
                return response()->json(['error' => 'Tài khoản đã tồn tại !'], 401);
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
                    'message' => 'Đăng kí tài khoản thành công !',
                    'user' => array_merge($userEmail->toArray(),$inforUser->toArray()),
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

            // verify email 
            $token = Str::random(32);
            $url =  UserEnum::DOMAIN_PATH . 'verify-email/' . $token;
            Log::info("Add jobs to Queue , Email: $user->email with URL: $url");
            Queue::push(new SendVerifyEmail($user->email, $url));
            $user->update(['token_verify_email' => $token]);
            // verify email 
            
            return response()->json([
                'message' => 'Đăng kí tài khoản thành công !',
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
            $ggUser = Socialite::driver('google')->stateless()->user();
            $inforUser = InforUser::where('google_id',$ggUser->id)->first();
            if ($inforUser) {
                $user = User::find($inforUser->id_user);
                if ($user->is_accept == 0) {
                    return response()->json(['error' => 'Tài khoản của bạn đã bị khóa hoặc chưa được phê duyệt !'], 401);
                } else {
                    Auth::login($user);
                    $this->token = auth()->guard('user_api')->login($user);
                    $user->access_token = $token =$this->respondWithToken($this->token)->getData()->access_token;

                    return view('user.oauth2gg', ['token' => $token]);
                    // return response()->json([
                    //     'message' => 'Login by Google successfully !',
                    //     'user' => array_merge($user->toArray(), $inforUser->toArray()),
                    // ], 201);
                }
            } else {
                $findEmail = User::where('email',$ggUser->email)->where('role','user')->first();
                if ($findEmail) {
                    if ($findEmail->is_accept == 0) {
                        return response()->json(['error' => 'Tài khoản của bạn đã bị khóa hoặc chưa được phê duyệt !'], 401);
                    } else {
                        $inforUser = InforUser::where('id_user',$findEmail->id)->first();
                        $inforUser->update([
                            'google_id' => $ggUser->id
                        ]);
                        Auth::login($findEmail);
                        $this->token = auth()->guard('user_api')->login($findEmail);
                        $findEmail->access_token = $token = $this->respondWithToken($this->token)->getData()->access_token;
    
                        return view('user.oauth2gg', ['token' => $token]);
                        // return response()->json([
                        //     'message' => 'Login by Google successfully !',
                        //     'user' => array_merge($findEmail->toArray(), $inforUser->toArray()),
                        // ], 201);
                    }
                } else {
                    $newUser = User::create([
                        'name' => $ggUser->name,
                        'email' => $ggUser->email,
                        'username' => 'user_' . $ggUser->id,
                        'avatar' => $ggUser->avatar,
                        'is_accept' => 1,
                        'role' => 'user',
                        'email_verified_at' => now()
                    ]);
                    Auth::login($newUser);
                    $this->token = auth()->guard('user_api')->login($newUser);
                    $newUser->access_token = $token = $this->respondWithToken($this->token)->getData()->access_token;

                    $newInforUser = InforUser::create([
                        'id_user' => $newUser->id,
                        'google_id' => $ggUser->id,
                        'gender' => 2,
                    ]);

                    return view('user.oauth2gg', ['token' => $token]);
                    // return response()->json([
                    //     'message' => 'User successfully registered',
                    //     'user' => array_merge($newUser->toArray(), $newInforUser->toArray()),
                    // ], 201);
                }
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e], 401);
        }
    }
    // Login by Google User 

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
        $user = User::find(auth('user_api')->user()->id);
        $oldEmail = $user->email;
        if($request->hasFile('avatar')) {
            if (!Str::startsWith($user->avatar, 'http')) {
                if ($user->avatar) {
                    File::delete($user->avatar);
                }
            }
            $avatar = $this->saveAvatar($request);
            $user->update(array_merge($request->all(),['avatar' => $avatar]));
        } else {
            $user->update($request->all());
        }
        $inforUser = InforUser::where('id_user', $user->id)->first();
        $inforUser->update($request->all());
        $message = 'Cập nhật thông tin cho tài khoản thành công !';
        // sendmail verify
        if($oldEmail != $request->email) {
            $token = Str::random(32);
            $url =  UserEnum::DOMAIN_PATH . 'verify-email/' . $token;
            Log::info("Add jobs to Queue , Email: $user->email with URL: $url");
            Queue::push(new SendVerifyEmail($user->email, $url));
            $content = 'Email của tài khoản của bạn đã được thay đổi thành ' . $user->email . '. Nếu bạn không phải là người thay đổi , hãy liên hệ với quản trị viên của hệ thống để được hỗ trợ . ';
            Queue::push(new SendMailNotify($oldEmail, $content));
            $user->update([
                'token_verify_email' => $token,
                'email_verified_at' => null,
            ]);
            $message = 'Cập nhật thông tin tài khoản thành công . Có một email xác nhận đã được gửi đến cho bạn , hãy kiểm tra và xác nhận nó !';
        } 
        // sendmail verify

        return response()->json([
            'message' => $message,
            'user' => array_merge($user->toArray(), $inforUser->toArray()),
        ], 201);
    }

    public function createPassword(RequestCreatePassword $request) {
        $user = User::find(auth('user_api')->user()->id);
        $user->update(['password' => Hash::make($request->get('new_password'))]);
        return response()->json([
            'message' => "Thay đổi mật khẩu thành công ! ",
        ],200);
    }


}

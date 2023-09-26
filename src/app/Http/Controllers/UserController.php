<?php

namespace App\Http\Controllers;

use App\Enums\UserEnum;
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
use App\Models\Admin;
use App\Models\InforDoctor;
use App\Models\InforHospital;
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
use Brian2694\Toastr\Facades\Toastr;

class UserController extends Controller
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

    public function login(Request $request)
    {
        
        $u = User::where('email',$request->email)->first();
        if(empty($u)){
            return response()->json(['error' => 'Email is incorrect !'], 401);
        }
        else {
            $is_accept = $u->is_accept;
            if($is_accept == 0){
                return response()->json(['error' => 'Your account has not been approved or may have been locked !'], 401);
            } 
            if($u->email_verified_at == null){
                return response()->json(['error' => 'This email has not been confirmed by the user, please go to your inbox to confirm this email !'], 401);
            } 
        }

        $credentials = request(['email', 'password']);
        $user = User::where('email',$request->email)->first();
        if (!$token = auth()->guard('user_api')->attempt($credentials)) {
            return response()->json(['error' => 'Either email or password is wrong. !'], 401);
        }

        $user->have_password = true;
        if(!$user->password) $user->have_password = false; // login by gg chưa có password 

        $inforUser = InforUser::where('id_user', $user->id)->first();
        if($user->role == 'hospital') {
            $inforUser = InforHospital::where('id_hospital', $user->id)->first();
        }
        if($user->role == 'doctor') {
            $inforUser = InforDoctor::where('id_doctor', $user->id)->first();
        }

        return response()->json([
            // "$user->role" => array_merge($user->toArray(), $inforUser->toArray()),
            "user" => array_merge($user->toArray(), $inforUser->toArray()),
            'message'=>$this->respondWithToken($token)
        ]);
    }

    public function logout()
    {
        auth('user_api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }
    
    public function changePassword(RequestChangePassword $request) {
        $user = User::find(auth('user_api')->user()->id);
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

    public function forgotForm(Request $request)
    {
        return view('user.reset_password');
    }
    
    public function forgotSend(Request $request)
    {
        try {
            $email = $request->email;
            $token = Str::random(32);
            $isUser = 1;
            $user = PasswordReset::where('email',$email)->where('is_user', $isUser)->first();
            if ($user) {
                $user->update(['token' => $token]);
            } else {
                PasswordReset::create([
                    'email' => $email,
                    'token' => $token,
                    'is_user' => $isUser
                ]);
            }
            $url = UserEnum::DOMAIN_PATH . 'forgot-form?token=' . $token;
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

    public function forgotUpdate(RequestCreatePassword $request)
    {
        try {
            $new_password = Hash::make($request->new_password);
            $passwordReset = PasswordReset::where('token',$request->token)->first();
            if ($passwordReset) { // user, doctor, hospital 
                if($passwordReset->is_user == 1) {
                    $user = User::where('email',$passwordReset->email)->first();
                    if ($user) {
                        $user->update(['password' => $new_password]);
                        $passwordReset->delete();
    
                        Toastr::success('Password Reset Success !');
                        return  redirect()->route('form_reset_password');
                    }
                    Toastr::warning('Can not find the account !');
                    return  redirect()->route('form_reset_password');
                }
                else { // admin, superamdin, manager
                    $admin = Admin::where('email',$passwordReset->email)->first(); 
                    if ($admin) {
                        $admin->update(['password' => $new_password]);
                        $passwordReset->delete();
    
                        Toastr::success('Password Reset Success !');
                        return  redirect()->route('form_reset_password');
                    }
                    Toastr::warning('Can not find the account !');
                    return  redirect()->route('form_reset_password');
                }

            } else {
                Toastr::warning('Token has expired !');
                return  redirect()->route('form_reset_password');
            }
        } catch (\Exception $e) {
        }
    }

    // verify email
    public function verifyEmail(Request $request, $token)
    {
        $user = User::where('token_verify_email', $token)->first();
        if($user) {
            $user->update([
                'email_verified_at' => now(),
                'token_verify_email' => null,
            ]);
            $status = true;
            Toastr::success('Your email has been verified !');
        } 
        else {
            $status = false;
            Toastr::warning('Token has expired !');
        }
        return view('user.status_verify_email', ['status' => $status]);
    }


}
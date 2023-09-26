<?php

namespace App\Http\Controllers;

use App\Enums\UserEnum;
use App\Http\Requests\RequestChangePassword;
use App\Http\Requests\RequestCreateNewAdmin;
use App\Http\Requests\RequestCreatePassword;
use App\Http\Requests\RequestUpdateAdmin;
use App\Jobs\SendForgotPasswordEmail;
use App\Jobs\SendMailNotify;
use App\Jobs\SendPasswordNewAdmin;
use App\Jobs\SendVerifyEmail;
use App\Models\Admin;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use SebastianBergmann\Environment\Console;
use Exception;
use Mail;        
use Illuminate\Support\Facades\DB;
use App\Mail\SendPassword;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;
use Faker\Factory ;
use Illuminate\Support\Facades\Storage;
use Brian2694\Toastr\Facades\Toastr;

class AdminController extends Controller
{
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->guard('admin_api')->factory()->getTTL() * 60
        ]);
    }

    public function login(Request $request)
    {
        $credentials = request(['email', 'password']);
        $user = Admin::where('email',$request->email)->first();
        if (!$token = auth()->guard('admin_api')->attempt($credentials)) {
            return response()->json(['error' => 'Either email or password is wrong. !'], 401);
        }

        return response()->json([
            'admin' => $user,
            'message'=>$this->respondWithToken($token)
        ]);
    }

    public function me()
    {
        return response()->json(auth('admin_api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('admin_api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function changePassword(RequestChangePassword $request) {
        $admin = Admin::find(auth('admin_api')->user()->id);
        if (!(Hash::check($request->get('current_password'), $admin->password))) {
            return response()->json([
                'message' => 'Your current password does not matches with the password.',
            ],400);
        }
        $admin->update(['password' => Hash::make($request->get('new_password'))]);
        return response()->json([
            'message' => "Password successfully changed !",
        ],200);
    }

    public function saveAvatar(Request $request){
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $filename =  pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/avatars/admins', $filename);
            return 'storage/image/avatars/admins/' . $filename;
        }
    }

    public function updateProfile(RequestUpdateAdmin $request, $id_admin)
    {
        $admin = Admin::find($id_admin);
        $oldEmail = $admin->email;
        if($request->hasFile('avatar')) {
            if ($admin->avatar) {
                File::delete($admin->avatar);
            }
            $avatar = $this->saveAvatar($request);
            $admin->update(array_merge($request->all(),['avatar' => $avatar]));
        } else {
            $admin->update($request->all());
        }
        $message = 'Admin successfully updated';
        // sendmail verify
        if($oldEmail != $request->email) {
            $token = Str::random(32);
            $url =  UserEnum::DOMAIN_PATH . 'admin/verify-email/' . $token;
            Queue::push(new SendVerifyEmail($admin->email, $url));
            $content = 'Your account has been transferred to email ' . $admin->email . '. If you are not the one making the change, please contact your system administrator for assistance. ';
            Queue::push(new SendMailNotify($oldEmail, $content));
            $admin->update([
                'token_verify_email' => $token,
                'email_verified_at' => null,
            ]);
            $message = 'Admin successfully updated . A confirmation email has been sent to this email, please check and confirm !';
        } 
        // sendmail verify
        return response()->json([
            'message' => $message,
            'admin' => $admin
        ], 201);
    }

    // verify email
    public function verifyEmail(Request $request, $token)
    {
        $admin = Admin::where('token_verify_email', $token)->first();
        if($admin) {
            $admin->update([
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
        return view('admin.status_verify_email', ['status' => $status]);
    }

    public function allAdmin()
    {
        $allAdmin = Admin::paginate(6);
        return response()->json([
            'message' => 'Get All Admin successfully',
            'admins' => $allAdmin
        ], 201);
    }

    public function allUser()
    {
        $allUser = User::paginate(6);
        return response()->json([
            'message' => 'Get All User successfully',
            'users' => $allUser
        ], 201);
    }

    public function forgotForm(Request $request)
    {
        return view('admin.reset_password');
    }
    
    public function forgotSend(Request $request)
    {
        try {
            $email = $request->email;
            $token = Str::random(32);
            $isUser = 0;
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
            $url = UserEnum::DOMAIN_PATH . 'admin/forgot-form?token=' . $token;
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
                        return  redirect()->route('admin_form_reset_password');
                    }
                    Toastr::warning('Can not find the account !');
                    return  redirect()->route('admin_form_reset_password');
                }

            } else {
                Toastr::warning('Token has expired !');
                return  redirect()->route('admin_form_reset_password');
            }
        } catch (\Exception $e) {
        }
    }
    /**
     * addAdmin
     * 
     * @param Request $request
     */
    public function addAdmin(RequestCreateNewAdmin $request)
    {
        $faker = Factory::create();
        $fakeImageUrl = $faker->imageUrl(200, 200, 'admins'); 
        $imageContent = file_get_contents($fakeImageUrl);
        $imageName = 'avatar_admin_' . time() . '.jpg'; 
        Storage::put('public/image/avatars/admins/' . $imageName, $imageContent);

        $new_password = Str::random(10);

        Admin::create([
            'email' => $request->email,
            'name' => $request->name,
            'password' => Hash::make($new_password),
            'role' => 'admin',
            'avatar' => 'storage/image/avatars/admins/' . $imageName,
        ]);
        Queue::push(new SendPasswordNewAdmin($request->email, $new_password));
        return response()->json([
            'message' => "Add Admin Success !",
        ],200);
    }

    /**
     * deleteAdmin
     * 
     * @param Request $request
     */
    public function deleteAdmin($id)
    {
        $role = auth('admin_api')->user()->role;
        if($role == 0) {
            return response()->json([
                'message' => "You do not have permission, only super admin has the right to delete !",
            ],401);
        } else {
            $admin = Admin::where('id', $id)->first();
            if ($admin->avatar) {
                File::delete($admin->avatar);
            }
            $admin->delete();
            return response()->json([
                'message' => "Delete Admin Success !",
            ],200);
        }
    }

    /**
     * editRole
     * 
     * @param Request $request
     */
    public function editRole(Request $request, $id)
    {
        $role = auth('admin_api')->user()->role;
        if($role == 0) {
            return response()->json([
                'message' => "You do not have permission, only super admin has the right to delete !",
            ],401);
        } else {
            $admin = Admin::where('id', $id)->first();
            $admin->update([
                'role' => $request->role,
            ]);
            return response()->json([
                'message' => "Change Role Admin Success !",
            ],200);
        }
    }

    /**
     * changeStatus
     * 
     * @param int $id 
     * @param Request $request  
     */
    public function changeAccept(Request $request, $id)
    {
        $user = User::where('id', $id)->first();
        $user->update([
            'is_accept' => $request->is_accept,
        ]);
        return response()->json([
            'message' => "Change Status User Success !",
        ],200);
    }
    
    
}

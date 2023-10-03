<?php

namespace App\Services;

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
use App\Models\PasswordReset;
use App\Models\User;
use App\Repositories\AdminInterface;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use Brian2694\Toastr\Facades\Toastr;
use Exception;
use Faker\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminService
{
    protected AdminInterface $adminRepository;

    public function __construct(
        AdminInterface $adminRepository,
    ) {
        $this->adminRepository = $adminRepository;
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->guard('admin_api')->factory()->getTTL() * 60,
        ]);
    }

    public function login(Request $request)
    {
        try {
            $credentials = request(['email', 'password']);
            $user = $this->adminRepository->findAdminByEmail($request->email);

            if ($user->email_verified_at == null) {
                return response()->json(['message' => 'Email này chưa được xác nhận , hãy kiểm tra và xác nhận nó trước khi đăng nhập !'], 400);
            }

            if (!$token = auth()->guard('admin_api')->attempt($credentials)) {
                return response()->json(['message' => 'Email hoặc mật khẩu không đúng !'], 400);
            }

            return response()->json([
                'admin' => $user,
                'message' => $this->respondWithToken($token),
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function changePassword(RequestChangePassword $request)
    {
        try {
            $admin = $this->adminRepository->findAdminById(auth('admin_api')->user()->id);
            if (!(Hash::check($request->get('current_password'), $admin->password))) {
                return response()->json([
                    'message' => 'Mật khẩu của bạn không chính xác !',
                ], 400);
            }
            $admin->update(['password' => Hash::make($request->get('new_password'))]);

            return response()->json([
                'message' => 'Thay đổi mật khẩu thành công !',
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function saveAvatar(Request $request)
    {
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/avatars/admins', $filename);

            return 'storage/image/avatars/admins/' . $filename;
        }
    }

    public function updateProfile(RequestUpdateAdmin $request, $id_admin)
    {
        try {
            $admin = $this->adminRepository->findAdminById($id_admin);
            $oldEmail = $admin->email;
            if ($request->hasFile('avatar')) {
                if ($admin->avatar) {
                    File::delete($admin->avatar);
                }
                $avatar = $this->saveAvatar($request);
                $admin->update(array_merge($request->all(), ['avatar' => $avatar]));
            } else {
                $admin->update($request->all());
            }
            $message = 'Cập nhật thông tin tài khoản admin thành công !';
            // sendmail verify
            if ($oldEmail != $request->email) {
                $token = Str::random(32);
                $url = UserEnum::DOMAIN_PATH . 'admin/verify-email/' . $token;
                Queue::push(new SendVerifyEmail($admin->email, $url));
                $content = 'Tài khoản của bạn đã thay đổi email thành ' . $admin->email . '. Nếu bạn không làm điều này, hãy liên hệ với quản trị viên của hệ thống để được hỗ trợ . ';
                Queue::push(new SendMailNotify($oldEmail, $content));
                $admin->update([
                    'token_verify_email' => $token,
                    'email_verified_at' => null,
                ]);
                $message = 'Cập nhật thông tin thành công . Một email xác nhận đã được gửi hãy kiểm tra mail và xác nhận nó !';
            }
            // sendmail verify
            return response()->json([
                'message' => $message,
                'admin' => $admin,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // verify email
    public function verifyEmail($token)
    {
        $admin = $this->adminRepository->findAdminByTokenVerifyEmail($token);
        if ($admin) {
            $admin->update([
                'email_verified_at' => now(),
                'token_verify_email' => null,
            ]);
            $status = true;
            Toastr::success('Email của bạn đã được xác nhận !');
        } else {
            $status = false;
            Toastr::warning('Token đã hết hạn !');
        }

        return view('admin.status_verify_email', ['status' => $status]);
    }

    public function allAdmin()
    {
        try {
            $allAdmin = $this->adminRepository->getAdmin()->paginate(6);

            return response()->json([
                'message' => 'Lấy tất cả quản trị viên thành công !',
                'admins' => $allAdmin,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function allUser()
    {
        try {
            $allUser = UserRepository::getUser()->paginate(6);

            return response()->json([
                'message' => 'Lấy tất cả người dùng thành công !',
                'users' => $allUser,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function forgotSend(Request $request)
    {
        try {
            $email = $request->email;
            $token = Str::random(32);
            $isUser = 0;
            $user = PasswordResetRepository::findPasswordReset($email, $isUser);
            if ($user) {
                $user->update(['token' => $token]);
            } else {
                PasswordResetRepository::createToken($email, $isUser, $token);
            }
            $url = UserEnum::DOMAIN_PATH . 'admin/forgot-form?token=' . $token;
            Log::info("Add jobs to Queue , Email: $email with URL: $url");
            Queue::push(new SendForgotPasswordEmail($email, $url));

            return response()->json([
                'message' => 'Gửi mail đặt lại mật khẩu thành công , hãy kiểm tra mail !',
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function forgotUpdate(RequestCreatePassword $request)
    {
        try {
            $new_password = Hash::make($request->new_password);
            $passwordReset = PasswordReset::where('token', $request->token)->first();
            if ($passwordReset) { // user, doctor, hospital
                if ($passwordReset->is_user == 1) {
                    $user = User::where('email', $passwordReset->email)->first();
                    if ($user) {
                        $user->update(['password' => $new_password]);
                        $passwordReset->delete();

                        Toastr::success('Đặt lại mật khẩu thành công !');

                        return redirect()->route('form_reset_password');
                    }
                    Toastr::warning('Không tìm thấy tài khoản !');

                    return redirect()->route('form_reset_password');
                } else { // admin, superamdin, manager
                    $admin = Admin::where('email', $passwordReset->email)->first();
                    if ($admin) {
                        $admin->update(['password' => $new_password]);
                        $passwordReset->delete();

                        Toastr::success('Đặt lại mật khẩu thành công !');

                        return redirect()->route('admin_form_reset_password');
                    }
                    Toastr::warning('Không tìm thấy tài khoản !');

                    return redirect()->route('admin_form_reset_password');
                }
            } else {
                Toastr::warning('Token đã hết hạn !');

                return redirect()->route('admin_form_reset_password');
            }
        } catch (Exception $e) {
        }
    }

    public function addAdmin(RequestCreateNewAdmin $request)
    {
        try {
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
                'message' => 'Thêm quản trị viên thành công !',
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteAdmin($id)
    {
        try {
            $role = auth('admin_api')->user()->role;
            if ($role == 0) {
                return response()->json([
                    'message' => 'Bạn không có quyền, chỉ có quản trị viên cấp cao mới có quyền xóa !',
                ], 400);
            } else {
                $admin = Admin::where('id', $id)->first();
                if ($admin->avatar) {
                    File::delete($admin->avatar);
                }
                $admin->delete();

                return response()->json([
                    'message' => 'Xóa tài khoản thành công !',
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function editRole(Request $request, $id)
    {
        try {
            $role = auth('admin_api')->user()->role;
            if ($role == 0) {
                return response()->json([
                    'message' => 'Bạn không có quyền , chỉ có quản trị viên cấp cao mới có quyền thay đổi role !',
                ], 400);
            } else {
                $admin = Admin::where('id', $id)->first();
                $admin->update([
                    'role' => $request->role,
                ]);

                return response()->json([
                    'message' => 'Thay đổi role cho quản trị viên thành công !',
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function changeAccept(Request $request, $id)
    {
        try {
            $user = User::where('id', $id)->first();
            $user->update([
                'is_accept' => $request->is_accept,
            ]);

            return response()->json([
                'message' => 'Thay đổi trạng thái của người dùng thành công !',
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

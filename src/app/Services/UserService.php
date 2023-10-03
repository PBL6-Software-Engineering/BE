<?php

namespace App\Services;

use App\Enums\UserEnum;
use App\Http\Requests\RequestChangePassword;
use App\Http\Requests\RequestCreatePassword;
use App\Jobs\SendForgotPasswordEmail;
use App\Models\Admin;
use App\Models\User;
use App\Repositories\AdminRepository;
use App\Repositories\InforDoctorRepository;
use App\Repositories\InforHospitalRepository;
use App\Repositories\InforUserRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserInterface;
use App\Repositories\UserRepository;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;

class UserService
{
    protected UserInterface $userRepository;

    public function __construct(
        UserInterface $userRepository,
    ) {
        $this->userRepository = $userRepository;
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('user_api')->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->guard('user_api')->factory()->getTTL() * 60,
        ]);
    }

    public function login(Request $request)
    {
        try {
            $user = $this->userRepository->findUserByEmail($request->email);
            if (empty($user)) {
                return response()->json(['message' => 'Email không tồn tại !'], 400);
            } else {
                $is_accept = $user->is_accept;
                if ($is_accept == 0) {
                    return response()->json(['message' => 'Tài khoản của bạn đã bị khóa hoặc chưa được phê duyệt !'], 400);
                }
                if ($user->email_verified_at == null) {
                    return response()->json(['message' => 'Email này chưa được xác nhận , hãy kiểm tra và xác nhận nó trước khi đăng nhập !'], 400);
                }
            }

            $credentials = request(['email', 'password']);
            if (!$token = auth()->guard('user_api')->attempt($credentials)) {
                return response()->json(['message' => 'Email hoặc mật khẩu không chính xác !'], 400);
            }

            $user->have_password = true;
            if (!$user->password) {
                $user->have_password = false;
            } // login by gg chưa có password

            $filter = (object) [
                'id_user' => $user->id ?? '',
                'id_doctor' => $user->id ?? '',
                'id_hospital' => $user->id ?? '',
            ];
            $inforUser = InforUserRepository::getInforUser($filter)->first();
            if ($user->role == 'hospital') {
                $inforUser = InforHospitalRepository::getInforHospital($filter)->first();
                $inforUser->infrastructure = json_decode($inforUser->infrastructure);
                $inforUser->location = json_decode($inforUser->location);
            }
            if ($user->role == 'doctor') {
                $inforUser = InforDoctorRepository::getInforDoctor($filter)->first();
            }

            return response()->json([
                'user' => array_merge($user->toArray(), $inforUser->toArray()),
                'message' => $this->respondWithToken($token),
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function changePassword(RequestChangePassword $request)
    {
        try {
            $user = UserRepository::findUserById(auth('user_api')->user()->id);
            if (!(Hash::check($request->get('current_password'), $user->password))) {
                return response()->json([
                    'message' => 'Mật khẩu không chính xác !',
                ], 400);
            }
            $data = ['password' => Hash::make($request->get('new_password'))];
            $user = UserRepository::updateUser($user->id, $data);

            return response()->json([
                'message' => 'Thay đổi mật khẩu thành công !',
            ], 200);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function forgotSend(Request $request)
    {
        try {
            $email = $request->email;
            $token = Str::random(32);
            $isUser = 1;
            $user = PasswordResetRepository::findPasswordReset($email, $isUser);
            if ($user) {
                $data = ['token' => $token];
                $user = UserRepository::updateUser($user->id, $data);
            } else {
                PasswordResetRepository::createToken($email, $isUser, $token);
            }
            $url = UserEnum::DOMAIN_PATH . 'forgot-form?token=' . $token;
            Log::info("Add jobs to Queue , Email: $email with URL: $url");
            Queue::push(new SendForgotPasswordEmail($email, $url));

            return response()->json([
                'message' => 'Gửi mail đặt lại mật khẩu thành công !',
            ], 200);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function forgotUpdate(RequestCreatePassword $request)
    {
        try {
            $new_password = Hash::make($request->new_password);
            $passwordReset = PasswordResetRepository::findByToken($request->token);
            if ($passwordReset) { // user, doctor, hospital
                if ($passwordReset->is_user == 1) {
                    $user = UserRepository::findUserByEmail($passwordReset->email);
                    if ($user) {
                        $data = ['password' => $new_password];
                        $user = UserRepository::updateUser($user->id, $data);
                        $passwordReset->delete();

                        Toastr::success('Đặt lại mật khẩu thành công !');

                        return redirect()->route('form_reset_password');
                    }
                    Toastr::warning('Không thể tìm thấy tài khoản !');

                    return redirect()->route('form_reset_password');
                } else { // admin, superamdin, manager
                    $admin = AdminRepository::findAdminByEmail($passwordReset->email);
                    if ($admin) {
                        $data = ['password' => $new_password];
                        $admin = AdminRepository::updateAdmin($admin->id, $data);
                        $passwordReset->delete();

                        Toastr::success('Đặt lại mật khẩu thành công !');

                        return redirect()->route('admin_form_reset_password');
                    }
                    Toastr::warning('Không tìm thấy tài khoản !');

                    return redirect()->route('admin_form_reset_password');
                }
            } else {
                Toastr::warning('Token đã hết hạn !');

                return redirect()->route('form_reset_password');
            }
        } catch (Throwable $e) {
        }
    }

    // verify email
    public function verifyEmail($token)
    {
        try {
            $user = $this->userRepository->findUserByTokenVerifyEmail($token);
            if ($user) {
                $data = [
                    'email_verified_at' => now(),
                    'token_verify_email' => null,
                ];
                $user = $this->userRepository->updateUser($user->id, $data);
                $status = true;
                Toastr::success('Email của bạn đã được xác nhận !');
            } else {
                $status = false;
                Toastr::warning('Token đã hết hạn !');
            }

            return view('user.status_verify_email', ['status' => $status]);
        } catch (Throwable $e) {
        }
    }

    public function getInforUser($id)
    {
        try {
            $user = $this->userRepository->findUserById($id);
            if (empty($user)) {
                return response()->json(['message' => 'Không tìm thấy tài khoản !'], 404);
            }

            return response()->json([
                'user' => $user,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

<?php

namespace App\Services;

use App\Enums\UserEnum;
use App\Http\Requests\RequestCreateInforUser;
use App\Http\Requests\RequestCreatePassword;
use App\Http\Requests\RequestUpdateUser;
use App\Jobs\SendMailNotify;
use App\Jobs\SendVerifyEmail;
use App\Models\User;
use App\Repositories\InforUserInterface;
use App\Repositories\InforUserRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class InforUserService
{
    protected InforUserInterface $inforUserRepository;

    public function __construct(
        InforUserInterface $inforUserRepository
    ) {
        $this->inforUserRepository = $inforUserRepository;
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('user_api')->refresh());
    }

    public function responseOK($status = 200, $data = null, $message = '')
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'status' => $status,
        ], $status);
    }

    public function responseError($status = 400, $message = '')
    {
        return response()->json([
            'message' => $message,
            'status' => $status,
        ], $status);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->guard('user_api')->factory()->getTTL() * 60,
        ]);
    }

    public function saveAvatar(Request $request)
    {
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_user_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/avatars/users/', $filename);

            return 'storage/image/avatars/users/' . $filename;
        }
    }

    public function register(RequestCreateInforUser $request)
    {
        try {
            $filter = [
                'email' => $request->email ?? '',
                'role' => 'user',
            ];
            $userEmail = UserRepository::findUser($filter)->first();
            if ($userEmail) {
                if ($userEmail['password']) {
                    return $this->responseError(400, 'Tài khoản đã tồn tại !');
                } else {
                    $avatar = $this->saveAvatar($request);

                    $data = array_merge(
                        $request->all(),
                        ['password' => Hash::make($request->password), 'avatar' => $avatar]
                    );
                    $userEmail = UserRepository::updateUser($userEmail->id, $data);

                    $filter = [
                        'id_user' => $userEmail->id ?? '',
                    ];
                    $inforUser = InforUserRepository::getInforUser($filter)->first();
                    $inforUser = InforUserRepository::updateInforUser($inforUser->id, $request->all());

                    // login
                    Auth::login($userEmail);
                    $this->token = auth()->guard('user_api')->login($userEmail);
                    $userEmail->access_token = $token = $this->respondWithToken($this->token)->getData()->access_token;
                    // login

                    $user = array_merge($userEmail->toArray(), $inforUser->toArray());

                    return $this->responseOK(200, $user, 'Đăng kí tài khoản thành công . ');
                }
            } else {
                $avatar = $this->saveAvatar($request);

                $data = array_merge(
                    $request->all(),
                    ['password' => Hash::make($request->password), 'is_accept' => 1, 'role' => 'user', 'avatar' => $avatar]
                );
                $user = UserRepository::createUser($data);

                $data = array_merge(
                    $request->all(),
                    ['id_user' => $user->id]
                );
                $inforUser = InforUserRepository::createInforUser($data);

                // verify email
                $token = Str::random(32);
                $url = UserEnum::DOMAIN_PATH . 'verify-email/' . $token;
                Log::info("Add jobs to Queue , Email: $user->email with URL: $url");
                Queue::push(new SendVerifyEmail($user->email, $url));
                $data = ['token_verify_email' => $token];
                $user = UserRepository::updateUser($user->id, $data);
                // verify email

                // login
                Auth::login($user);
                $this->token = auth()->guard('user_api')->login($user);
                $user->access_token = $token = $this->respondWithToken($this->token)->getData()->access_token;
                // login

                $user = array_merge($user->toArray(), $inforUser->toArray());

                return $this->responseOK(200, $user, 'Đăng kí tài khoản thành công . Hãy kiểm tra mail và xác nhận nó !');
            }
        } catch (Throwable $e) {
            return $this->responseError(400, $e->getMessage());
        }
    }

    // Login by Google User
    public function handleGoogleCallback()
    {
        try {
            $ggUser = Socialite::driver('google')->stateless()->user();
            $filter = [
                'google_id' => $ggUser->id ?? '',
            ];
            $inforUser = InforUserRepository::getInforUser($filter)->first();
            if ($inforUser) {
                $user = UserRepository::findUserById($inforUser->id_user);
                if ($user->is_accept == 0) {
                    return $this->responseError(400, 'Tài khoản của bạn đã bị khóa hoặc chưa được phê duyệt !');
                } else {
                    Auth::login($user);
                    $this->token = auth()->guard('user_api')->login($user);
                    $user->access_token = $token = $this->respondWithToken($this->token)->getData()->access_token;

                    return view('user.oauth2gg', ['token' => $token]);
                    // return response()->json([
                    //     'message' => 'Login by Google successfully !',
                    //     'user' => array_merge($user->toArray(), $inforUser->toArray()),
                    // ], 200);
                }
            } else {
                $filter = [
                    'email' => $ggUser->email ?? '',
                    'role' => 'user',
                ];
                $findEmail = UserRepository::findUser($filter)->first();
                if ($findEmail) {
                    if ($findEmail->is_accept == 0) {
                        return $this->responseError(400, 'Tài khoản của bạn đã bị khóa hoặc chưa được phê duyệt !');
                    } else {
                        $filter = [
                            'id_user' => $findEmail->id ?? '',
                        ];
                        $inforUser = InforUserRepository::getInforUser($filter)->first();
                        $data = [
                            'google_id' => $ggUser->id,
                        ];
                        $inforUser = InforUserRepository::updateInforUser($inforUser->id, $data);
                        Auth::login($findEmail);
                        $this->token = auth()->guard('user_api')->login($findEmail);
                        $findEmail->access_token = $token = $this->respondWithToken($this->token)->getData()->access_token;

                        return view('user.oauth2gg', ['token' => $token]);
                        // return response()->json([
                        //     'message' => 'Login by Google successfully !',
                        //     'user' => array_merge($findEmail->toArray(), $inforUser->toArray()),
                        // ], 200);
                    }
                } else {
                    $data = [
                        'name' => $ggUser->name,
                        'email' => $ggUser->email,
                        'username' => 'user_' . $ggUser->id,
                        'avatar' => $ggUser->avatar,
                        'is_accept' => 1,
                        'role' => 'user',
                        'email_verified_at' => now(),
                    ];

                    $newUser = UserRepository::createUser($data);
                    Auth::login($newUser);
                    $this->token = auth()->guard('user_api')->login($newUser);
                    $newUser->access_token = $token = $this->respondWithToken($this->token)->getData()->access_token;

                    $data = [
                        'id_user' => $newUser->id,
                        'google_id' => $ggUser->id,
                        'gender' => 2,
                    ];
                    $newInforUser = InforUserRepository::createInforUser($data);

                    return view('user.oauth2gg', ['token' => $token]);
                    // return response()->json([
                    //     'message' => 'User successfully registered',
                    //     'user' => array_merge($newUser->toArray(), $newInforUser->toArray()),
                    // ], 200);
                }
            }
        } catch (Throwable $e) {
            return $this->responseError(400, $e->getMessage());
        }
    }
    // Login by Google User

    public function profile()
    {
        try {
            $user = UserRepository::findUserById(auth('user_api')->user()->id);
            $inforUser = InforUserRepository::getInforUser(['id_user' => $user->id])->first();

            $arrUser = array_merge($user->toArray(), $inforUser->toArray());

            return $this->responseOK(200, $arrUser, 'Xem thông tin tài khoản thành công !');
        } catch (Throwable $e) {
            return $this->responseError(400, $e->getMessage());
        }
    }

    public function updateProfile(RequestUpdateUser $request)
    {
        try {
            $user = UserRepository::findUserById(auth('user_api')->user()->id);
            $oldEmail = $user->email;
            if ($request->hasFile('avatar')) {
                if (!Str::startsWith($user->avatar, 'http')) {
                    if ($user->avatar) {
                        File::delete($user->avatar);
                    }
                }
                $avatar = $this->saveAvatar($request);
                $user = UserRepository::updateUser($user->id, array_merge($request->all(), ['avatar' => $avatar]));
            } else {
                $user = UserRepository::updateUser($user->id, $request->all());
            }
            $inforUser = InforUserRepository::getInforUser(['id_user' => $user->id])->first();
            $inforUser = InforUserRepository::updateInforUser($inforUser->id, $request->all());
            $message = 'Cập nhật thông tin cho tài khoản thành công !';
            // sendmail verify
            if ($oldEmail != $request->email) {
                $token = Str::random(32);
                $url = UserEnum::DOMAIN_PATH . 'verify-email/' . $token;
                Log::info("Add jobs to Queue , Email: $user->email with URL: $url");
                Queue::push(new SendVerifyEmail($user->email, $url));
                $content = 'Email của tài khoản của bạn đã được thay đổi thành ' . $user->email . '. Nếu bạn không phải là người thay đổi , hãy liên hệ với quản trị viên của hệ thống để được hỗ trợ . ';
                Queue::push(new SendMailNotify($oldEmail, $content));

                $data = [
                    'token_verify_email' => $token,
                    'email_verified_at' => null,
                ];
                $user = UserRepository::updateUser($user->id, $data);
                $message = 'Cập nhật thông tin tài khoản thành công . Có một email xác nhận đã được gửi đến cho bạn , hãy kiểm tra và xác nhận nó !';
            }
            // sendmail verify

            $arrUser = array_merge($user->toArray(), $inforUser->toArray());

            return $this->responseOK(200, $arrUser, $message);
        } catch (Throwable $e) {
            return $this->responseError(400, $e->getMessage());
        }
    }

    public function createPassword(RequestCreatePassword $request)
    {
        try {
            $user = UserRepository::findUserById(auth('user_api')->user()->id);
            $user = UserRepository::updateUser($user->id, ['password' => Hash::make($request->get('new_password'))]);

            return $this->responseOK(200, null, 'Tạo mật khẩu thành công ! ');
        } catch (Throwable $e) {
            return $this->responseError(400, $e->getMessage());
        }
    }
}

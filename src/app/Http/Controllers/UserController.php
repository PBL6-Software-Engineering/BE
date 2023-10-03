<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestChangePassword;
use App\Http\Requests\RequestCreatePassword;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Throwable;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function login(Request $request)
    {
        return $this->userService->login($request);
    }

    public function logout()
    {
        auth('user_api')->logout();

        return response()->json(['message' => 'Đăng xuất thành công !']);
    }

    public function changePassword(RequestChangePassword $request)
    {
        return $this->userService->changePassword($request);
    }

    public function forgotForm(Request $request)
    {
        return view('user.reset_password');
    }

    public function forgotSend(Request $request)
    {
        return $this->userService->forgotSend($request);
    }

    public function forgotUpdate(RequestCreatePassword $request)
    {
        return $this->userService->forgotUpdate($request);
    }

    // verify email
    public function verifyEmail($token)
    {
        return $this->userService->verifyEmail($token);
    }

    public function getInforUser($id)
    {
        try {
            $user = User::find($id);
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

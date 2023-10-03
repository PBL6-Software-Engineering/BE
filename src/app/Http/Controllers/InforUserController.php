<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateInforUser;
use App\Http\Requests\RequestCreatePassword;
use App\Http\Requests\RequestUpdateUser;
use App\Services\InforUserService;
use Laravel\Socialite\Facades\Socialite;

class InforUserController extends Controller
{
    protected InforUserService $inforUserService;

    public function __construct(InforUserService $inforUserService)
    {
        $this->inforUserService = $inforUserService;
    }

    public function register(RequestCreateInforUser $request)
    {
        return $this->inforUserService->register($request);
    }

    // Login by Google User
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        return $this->inforUserService->handleGoogleCallback();
    }
    // Login by Google User

    public function profile()
    {
        return $this->inforUserService->profile();
    }

    public function updateProfile(RequestUpdateUser $request)
    {
        return $this->inforUserService->updateProfile($request);
    }

    public function createPassword(RequestCreatePassword $request)
    {
        return $this->inforUserService->createPassword($request);
    }
}

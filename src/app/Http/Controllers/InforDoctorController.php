<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestUpdateDoctor;
use App\Services\InforDoctorService;

class InforDoctorController extends Controller
{
    protected InforDoctorService $inforDoctorService;

    public function __construct(InforDoctorService $inforDoctorService)
    {
        $this->inforDoctorService = $inforDoctorService;
    }

    public function profile()
    {
        return $this->inforDoctorService->profile();
    }

    public function updateProfile(RequestUpdateDoctor $request, $id_user)
    {
        return $this->inforDoctorService->updateProfile($request, $id_user);
    }
}

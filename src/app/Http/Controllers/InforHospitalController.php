<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateInforHospital;
use App\Http\Requests\RequestCreateNewDoctor;
use App\Http\Requests\RequestUpdateHospital;
use App\Services\InforHospitalService;

class InforHospitalController extends Controller
{
    protected InforHospitalService $inforHospitalService;

    public function __construct(InforHospitalService $inforHospitalService)
    {
        $this->inforHospitalService = $inforHospitalService;
    }

    public function register(RequestCreateInforHospital $request)
    {
        return $this->inforHospitalService->register($request);
    }

    public function profile()
    {
        return $this->inforHospitalService->profile();
    }

    public function updateProfile(RequestUpdateHospital $request, $id_user)
    {
        return $this->inforHospitalService->updateProfile($request, $id_user);
    }

    public function addDoctor(RequestCreateNewDoctor $request)
    {
        return $this->inforHospitalService->addDoctor($request);
    }
}

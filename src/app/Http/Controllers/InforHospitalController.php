<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestChangeConfirmDoctor;
use App\Http\Requests\RequestCreateInforHospital;
use App\Http\Requests\RequestCreateNewDoctor;
use App\Http\Requests\RequestUpdateHospital;
use App\Services\InforHospitalService;
use Illuminate\Http\Request;

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

    public function updateProfile(RequestUpdateHospital $request)
    {
        return $this->inforHospitalService->updateProfile($request);
    }

    public function addDoctor(RequestCreateNewDoctor $request)
    {
        return $this->inforHospitalService->addDoctor($request);
    }

    public function allDoctor(Request $request)
    {
        return $this->inforHospitalService->allDoctor($request);
    }

    public function changeConfirm(RequestChangeConfirmDoctor $request, $id) {
        return $this->inforHospitalService->changeConfirm($request, $id);
    }
}

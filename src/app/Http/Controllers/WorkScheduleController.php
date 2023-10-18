<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateWorkScheduleAdvise;
use App\Http\Requests\RequestCreateWorkScheduleService;
use App\Services\WorkScheduleService;

class WorkScheduleController extends Controller
{
    protected WorkScheduleService $workScheduleService;

    public function __construct(WorkScheduleService $workScheduleService)
    {
        $this->workScheduleService = $workScheduleService;
    }

    public function addAdvise(RequestCreateWorkScheduleAdvise $request)
    {
        return $this->workScheduleService->addAdvise($request);
    }

    public function addService(RequestCreateWorkScheduleService $request)
    {
        return $this->workScheduleService->addService($request);
    }

    
}

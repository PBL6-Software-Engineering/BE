<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateTimeWork;
use App\Http\Requests\RequestUpdateTimeWork;
use App\Services\TimeWorkService;
use Illuminate\Http\Request;

class TimeWorkController extends Controller
{
    protected TimeWorkService $timeWorkService;

    public function __construct(TimeWorkService $timeWorkService)
    {
        $this->timeWorkService = $timeWorkService;
    }

    public function edit(RequestUpdateTimeWork $request)
    {
        return $this->timeWorkService->edit($request);
    }

    public function detail()
    {
        return $this->timeWorkService->detail();
    }
}

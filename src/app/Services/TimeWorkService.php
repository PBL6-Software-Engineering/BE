<?php

namespace App\Services;

use App\Http\Requests\RequestUpdateTimeWork;
use App\Repositories\TimeWorkInterface;
use Throwable;

class TimeWorkService
{
    protected TimeWorkInterface $timeWorkRepository;

    public function __construct(TimeWorkInterface $timeWorkRepository)
    {
        $this->timeWorkRepository = $timeWorkRepository;
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

    public function edit(RequestUpdateTimeWork $request)
    {
        try {
            $user = auth()->guard('user_api')->user();
            $filter = (object) [
                'id_hospital' => $user->id,
            ];
            $timeWork = $this->timeWorkRepository->getTimeWork($filter)->first();
            $request->merge([
                'times' => json_encode($request->times),
            ]);
            $timeWork = $this->timeWorkRepository->updateTimeWork($timeWork, $request->all());
            $timeWork->times = json_decode($timeWork->times);

            return $this->responseOK(200, $timeWork, 'Chỉnh sửa lịch làm việc thành công !');
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function detail()
    {
        try {
            $user = auth()->guard('user_api')->user();
            $filter = (object) [
                'id_hospital' => $user->id,
            ];
            $timeWork = $this->timeWorkRepository->getTimeWork($filter)->first();
            $timeWork->times = json_decode($timeWork->times);

            return $this->responseOK(200, $timeWork, 'Xem chi tiết lịch làm việc thành công !');
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

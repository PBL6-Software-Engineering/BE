<?php

namespace App\Repositories;

use App\Models\HospitalService;

class HospitalServiceRepository extends BaseRepository implements HospitalServiceInterface
{
    public function getModel()
    {
        return HospitalService::class;
    }

    public static function getHospitalService($filter)
    {
        $filter = (object) $filter;
        $data = (new self)->model
            ->when(!empty($filter->email), function ($q) use ($filter) {
                $q->where('email', '=', "$filter->email");
            })
            ->when(!empty($filter->name), function ($q) use ($filter) {
                $q->where('name', 'like', "%$filter->name%");
            })
            ->when(!empty($filter->start_at), function ($query) use ($filter) {
                $query->whereDate('created_at', '>=', $filter->start_at);
            })
            ->when(!empty($filter->end_at), function ($query) use ($filter) {
                $query->whereDate('created_at', '<=', $filter->end_at);
            });

        return $data;
    }
}

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
            ->when(!empty($filter->id_hospital_department), function ($query) use ($filter) {
                $query->where('id_hospital_department', '=', $filter->id_hospital_department);
            });

        return $data;
    }
}

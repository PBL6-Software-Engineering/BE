<?php

namespace App\Repositories;

use App\Models\HealthInsuranceHospital;

class HealthInsuranceHospitalRepository extends BaseRepository implements HealthInsuranceHospitalInterface
{
    public function getModel()
    {
        return HealthInsuranceHospital::class;
    }

    public static function getHealInsurHos($filter)
    {
        $filter = (object) $filter;
        $data = (new self)->model
            ->when(!empty($filter->id_health_insurance), function ($q) use ($filter) {
                $q->where('id_health_insurance', $filter->id_health_insurance);
            });

        return $data;
    }
}

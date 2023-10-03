<?php

namespace App\Repositories;

use App\Models\InforHospital;

/**
 * Class ExampleRepository.
 */
class InforHospitalRepository extends BaseRepository implements InforHospitalInterface
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function getModel()
    {
        return InforHospital::class;
    }

    public static function getInforHospital($filter)
    {
        $filter = (object) $filter;
        $user = (new self)->model
            ->when(!empty($filter->id), function ($q) use ($filter) {
                $q->where('id', $filter->id);
            })
            ->when(!empty($filter->id_hospital), function ($q) use ($filter) {
                $q->where('id_hospital', $filter->id_hospital);
            });

        return $user;
    }
}

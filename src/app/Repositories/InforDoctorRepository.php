<?php

namespace App\Repositories;

use App\Models\InforDoctor;

/**
 * Class ExampleRepository.
 */
class InforDoctorRepository extends BaseRepository implements InforDoctorInterface
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function getModel()
    {
        return InforDoctor::class;
    }

    public static function getInforDoctor($filter)
    {
        $filter = (object) $filter;
        $user = (new self)->model
            ->when(!empty($filter->id), function ($q) use ($filter) {
                $q->where('id', $filter->id);
            })
            ->when(!empty($filter->id_doctor), function ($q) use ($filter) {
                $q->where('id_doctor', $filter->id_doctor);
            });

        return $user;
    }
}

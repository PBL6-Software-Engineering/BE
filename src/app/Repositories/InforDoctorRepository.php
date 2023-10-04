<?php

namespace App\Repositories;

use App\Models\InforDoctor;
use Illuminate\Support\Facades\DB;
use Throwable;

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

    public static function createDoctor($data)
    {
        DB::beginTransaction();
        try {
            $newDoctor = (new self)->model->create($data);
            DB::commit();

            return $newDoctor;
        } catch (Throwable $e) {
            DB::rollback();
            throw $e;
        }
    }

    public static function updateInforDoctor($id, $data)
    {
        DB::beginTransaction();
        try {
            $inforDoctor = (new self)->model->find($id);
            $inforDoctor->update($data);
            DB::commit();

            return $inforDoctor;
        } catch (Throwable $e) {
            DB::rollback();
            throw $e;
        }
    }
}

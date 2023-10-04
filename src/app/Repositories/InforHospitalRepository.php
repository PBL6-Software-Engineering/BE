<?php

namespace App\Repositories;

use App\Models\InforHospital;
use Illuminate\Support\Facades\DB;
use Throwable;

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

    public static function createHospital($data)
    {
        DB::beginTransaction();
        try {
            $newHospital = (new self)->model->create($data);
            DB::commit();

            return $newHospital;
        } catch (Throwable $e) {
            DB::rollback();
            throw $e;
        }
    }

    public static function updateInforHospital($id, $data)
    {
        DB::beginTransaction();
        try {
            $inforHospital = (new self)->model->find($id);
            $inforHospital->update($data);
            DB::commit();

            return $inforHospital;
        } catch (Throwable $e) {
            DB::rollback();
            throw $e;
        }
    }
}

<?php

namespace App\Repositories;

use App\Models\HospitalDepartment;
use Illuminate\Support\Facades\DB;
use Throwable;

class HospitalDepartmentRepository extends BaseRepository implements HospitalDepartmentInterface
{
    public function getModel()
    {
        return HospitalDepartment::class;
    }

    public static function getHospitalDepartment($filter)
    {
        $filter = (object) $filter;
        $data = (new self)->model
            ->when(!empty($filter->id), function ($q) use ($filter) {
                $q->where('id', $filter->id);
            })
            ->when(!empty($filter->id_department), function ($q) use ($filter) {
                $q->where('id_department', $filter->id_department);
            });

        return $data;
    }

    public static function updateHospitalDepartment($result, $data)
    {
        DB::beginTransaction();
        try {
            $result->update($data);
            DB::commit();

            return $result;
        } catch (Throwable $e) {
            DB::rollback();
            throw $e;
        }
    }
}

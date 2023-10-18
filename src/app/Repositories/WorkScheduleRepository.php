<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\WorkSchedule;
use Illuminate\Support\Facades\DB;
use Throwable;

class WorkScheduleRepository extends BaseRepository implements WorkScheduleInterface
{
    public function getModel()
    {
        return WorkSchedule::class;
    }

    public static function findById($id)
    {
        return (new self)->model->find($id);
    }

    public static function createWorkSchedule($data)
    {
        DB::beginTransaction();
        try {
            $newWorkSchedule = (new self)->model->create($data);
            DB::commit();

            return $newWorkSchedule;
        } catch (Throwable $e) {
            DB::rollback();
            throw $e;
        }
    }

    public static function getWorkSchedule($filter)
    {
        $filter = (object) $filter;
        $data = (new self)->model
            ->when(!empty($filter->id), function ($q) use ($filter) {
                $q->where('id', $filter->id);
            })
            ->when(!empty($filter->id_doctor), function ($q) use ($filter) {
                $q->where('id_doctor', $filter->id_doctor);
            })
            ->when(!empty($filter->time), function ($q) use ($filter) {
                $q->whereJsonContains('time', [
                    'date' => $filter->time['date'],
                    'interval' => $filter->time['interval']
                ]);
            })
            ->when(isset($filter->id_service), function ($query) use ($filter) {
                $query->where('id_service', $filter->id_service === 'advise' ? null : $filter->id_service);
            });
        return $data;
    }

    // public static function updateCategory($id, $data)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $category = (new self)->model->find($id);
    //         $category->update($data);
    //         DB::commit();

    //         return $category;
    //     } catch (Throwable $e) {
    //         DB::rollback();
    //         throw $e;
    //     }
    // }

    // public static function updateResultCategory($result, $data)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $result->update($data);
    //         DB::commit();

    //         return $result;
    //     } catch (Throwable $e) {
    //         DB::rollback();
    //         throw $e;
    //     }
    // }

    // public static function searchCategory($filter)
    // {
    //     $filter = (object) $filter;
    //     $data = (new self)->model->orderBy($filter->orderBy, $filter->orderDirection)
    //         ->where('name', 'LIKE', '%' . $filter->search . '%');

    //     return $data;
    // }
}

<?php

namespace App\Repositories;

use App\Models\InforUser;

/**
 * Class ExampleRepository.
 */
class InforUserRepository extends BaseRepository implements InforUserInterface
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function getModel()
    {
        return InforUser::class;
    }

    public static function getInforUser($filter)
    {
        $user = (new self)->model
            ->when(!empty($filter->id), function ($q) use ($filter) {
                $q->where('id', '=', "$filter->id");
            })
            ->when(!empty($filter->id_user), function ($q) use ($filter) {
                $q->where('id_user', '=', "$filter->id_user");
            });

        return $user;
    }
}

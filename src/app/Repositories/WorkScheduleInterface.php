<?php

namespace App\Repositories;

interface WorkScheduleInterface extends RepositoryInterface
{
    public static function findById($id);
    
    public static function createWorkSchedule($data);

    public static function getWorkSchedule($filter);

    // public static function updateCategory($id, $data);

    // public static function updateResultCategory($result, $data);

    // public static function searchCategory($filter);
}

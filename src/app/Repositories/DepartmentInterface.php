<?php

namespace App\Repositories;

interface DepartmentInterface extends RepositoryInterface
{
    public static function getDepartment($filter);
}

<?php

namespace App\Repositories;

interface HospitalDepartmentInterface extends RepositoryInterface
{
    public static function getHospitalDepartment($filter);
}

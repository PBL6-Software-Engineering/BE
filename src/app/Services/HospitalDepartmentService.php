<?php

namespace App\Services;

use App\Repositories\HospitalDepartmentInterface;

class HospitalDepartmentService
{
    protected HospitalDepartmentInterface $hospitalDepartment;

    public function __construct(
        HospitalDepartmentInterface $hospitalDepartment
    ) {
        $this->hospitalDepartment = $hospitalDepartment;
    }
}

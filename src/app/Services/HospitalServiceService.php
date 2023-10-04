<?php

namespace App\Services;

use App\Repositories\HospitalServiceInterface;

class HospitalServiceService
{
    protected HospitalServiceInterface $hospitalService;

    public function __construct(HospitalServiceInterface $hospitalService)
    {
        $this->hospitalService = $hospitalService;
    }
}

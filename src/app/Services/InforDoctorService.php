<?php

namespace App\Services;

use App\Repositories\InforDoctorInterface;

class InforDoctorService
{
    protected InforDoctorInterface $inforDoctorRepository;

    public function __construct(
        InforDoctorInterface $inforDoctorRepository
    ) {
        $this->inforDoctorRepository = $inforDoctorRepository;
    }
}

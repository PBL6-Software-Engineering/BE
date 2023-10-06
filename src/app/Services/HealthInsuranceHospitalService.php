<?php

namespace App\Services;

use App\Repositories\ExampleInterface;

class HealthInsuranceHospitalService
{
    protected ExampleInterface $exampleRepository;

    public function __construct(ExampleInterface $exampleRepository)
    {
        $this->exampleRepository = $exampleRepository;
    }
}

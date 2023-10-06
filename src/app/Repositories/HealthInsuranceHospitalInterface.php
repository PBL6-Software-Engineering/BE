<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface HealthInsuranceHospitalInterface extends RepositoryInterface
{
    public static function getHealInsurHos($filter);
}

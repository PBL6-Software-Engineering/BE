<?php

namespace App\Repositories;

interface HospitalServiceInterface extends RepositoryInterface
{
    public static function getHospitalService($filter);
}

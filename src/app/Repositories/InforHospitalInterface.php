<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface InforHospitalInterface extends RepositoryInterface
{
    public static function getInforHospital($filter);
}
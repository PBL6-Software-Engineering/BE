<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface InforDoctorInterface extends RepositoryInterface
{
    public static function getInforDoctor($filter);
}
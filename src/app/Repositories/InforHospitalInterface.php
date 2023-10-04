<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface InforHospitalInterface extends RepositoryInterface
{
    public static function getInforHospital($filter);

    public static function createHospital($data);

    public static function updateInforHospital($id, $data);
}

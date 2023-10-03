<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface InforUserInterface extends RepositoryInterface
{
    public static function getInforUser($filter);
}

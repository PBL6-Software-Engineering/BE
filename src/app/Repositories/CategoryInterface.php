<?php

namespace App\Repositories;

interface CategoryInterface extends RepositoryInterface
{
    public static function getCategory($filter);

    public static function createCategory($data);

    public static function updateCategory($id, $data);

    public static function searchCategory($filter);
}

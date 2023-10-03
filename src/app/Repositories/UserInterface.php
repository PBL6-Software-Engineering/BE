<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface UserInterface extends RepositoryInterface
{
    public static function getUser();

    public static function findUserByEmail($email);

    public static function findUserById($id);

    public static function updateUser($id, $data);

    public function findUserByTokenVerifyEmail($token);
}

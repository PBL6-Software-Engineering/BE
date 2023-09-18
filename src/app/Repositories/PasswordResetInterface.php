<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface PasswordResetInterface extends RepositoryInterface
{
    public static function findPasswordReset($email, $is_user);

    public static function updateToken($user, $token);

    public static function createToken($email, $token, $is_user);

    public static function findPasswordResetByToken($token);

    public static function deleteUser($user, $is_user);
}

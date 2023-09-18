<?php

namespace App\Repositories;

use App\Models\PasswordReset;

/**
 * Class ExampleRepository.
 */
class PasswordResetRepository extends BaseRepository implements PasswordResetInterface
{
    public function getModel()
    {
        return PasswordReset::class;
    }

    /**
     * findPasswordReset
     *
     * @param string $email
     * @return object
     */
    public static function findPasswordReset($email, $is_user)
    {
        return (new self)->model->where('email', '=', $email)
            ->where('is_user', '=', $is_user)
            ->first();
    }

    /**
     * findPasswordResetByToken
     *
     * @param string $token
     * @return object
     */
    public static function findPasswordResetByToken($token)
    {
        return (new self)->model
            ->when($token, fn ($q) => $q->where('token', '=', $token))
            ->first();
    }

    /**
     * updateToken
     *
     * @param object $user
     * @param string $token
     */
    public static function updateToken($user, $token)
    {
        $user = (new self)->model->find($user->id);
        $updateData = [
            'token' => $token,
        ];
        $user->update($updateData);
    }

    /**
     * createToken
     *
     * @param string $email
     * @param string $token
     * @return object
     */
    public static function createToken($email, $token, $is_user)
    {
        return (new self)->model->create([
            'email' => $email,
            'is_user' => $is_user,
            'token' => $token,
        ]);
    }

    /**
     * deleteUser
     *
     * @param object $user
     */
    public static function deleteUser($user, $is_user)
    {
        $user = (new self)->model
            ->when($user->email, fn ($q) => $q->where('email', '=', $user->email))
            ->when($is_user, fn ($q) => $q->where('is_user', '=', $is_user))
            ->first();
        $user->delete();
    }
}

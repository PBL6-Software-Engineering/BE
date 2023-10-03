<?php

namespace App\Repositories;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class UserRepository extends BaseRepository implements UserInterface
{
    public function getModel()
    {
        return User::class;
    }

    public static function getUser()
    {
        return (new self)->model;
    }

    public static function findUserByEmail($email)
    {
        return (new self)->model->where('email', $email)->first();
    }

    public static function findUserById($id)
    {
        return (new self)->model->find($id);
    }

    public static function updateUser($id, $data)
    {
        DB::beginTransaction();
        try {
            $user = (new self)->model->find($id);
            $user->update($data);
            DB::commit();

            return $user;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}

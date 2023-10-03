<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

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
        } catch (Throwable $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function findUserByTokenVerifyEmail($token)
    {
        return $this->model->where('token_verify_email', $token)->first();
    }

    public static function findUser($filter)
    {
        $filter = (object) $filter;
        $user = (new self)->model
            ->when(!empty($filter->id), function ($q) use ($filter) {
                $q->where('id', $filter->id);
            })
            ->when(!empty($filter->email), function ($q) use ($filter) {
                $q->where('email', $filter->email);
            })
            ->when(!empty($filter->role), function ($q) use ($filter) {
                $q->where('role', $filter->role);
            });

        return $user;
    }

    public static function createUser($data)
    {
        DB::beginTransaction();
        try {
            $newUser = (new self)->model->create($data);
            DB::commit();

            return $newUser;
        } catch (Throwable $e) {
            DB::rollback();
            throw $e;
        }
    }
}

<?php

namespace App\Repositories;

use App\Models\Admin;
use Exception;
use Illuminate\Support\Facades\DB;

class AdminRepository extends BaseRepository implements AdminInterface
{
    public function getModel()
    {
        return Admin::class;
    }

    public function getAdmin()
    {
        return $this->model;
    }

    public static function findAdminByEmail($email)
    {
        return (new self)->model->where('email', $email)->first();
    }

    public function findAdminById($id)
    {
        return $this->model->find($id);
    }

    public function findAdminByTokenVerifyEmail($token)
    {
        return $this->model->where('token_verify_email', $token)->first();
    }

    public function createAdmin($data)
    {
        DB::beginTransaction();
        try {
            $newAdmin = $this->model->create($data);
            DB::commit();

            return $newAdmin;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public static function updateAdmin($id, $data)
    {
        DB::beginTransaction();
        try {
            $admin = (new self)->model->find($id);
            $admin->update($data);
            DB::commit();

            return $admin;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}

<?php

namespace App\Repositories;

use App\Models\Admin;

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

    public function findAdminByEmail($email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function findAdminById($id)
    {
        return $this->model->find($id);
    }

    public function findAdminByTokenVerifyEmail($token)
    {
        return $this->model->where('token_verify_email', $token)->first();
    }
}

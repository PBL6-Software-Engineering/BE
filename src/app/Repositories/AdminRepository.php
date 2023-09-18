<?php

namespace App\Repositories;

use App\Models\Admin;

class AdminRepository extends BaseRepository implements AdminInterface
{
    public function getModel()
    {
        return Admin::class;
    }

    /**
     * getAllAdmin
     *
     * @return array
     */
    public function getAllAdmin()
    {
        return $this->model;
    }

    /**
     * addAdmin
     *
     * @param $admin
     */
    public function addAdmin($admin)
    {
        return $this->model->create([
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 0,
            'avatar' => 'storage/Blog/image/avatars/admin.png',
            'password' => $admin->password,
        ]);
    }

    /**
     * changeRole
     *
     * @param object $filter
     */
    public function changeRole($input)
    {
        $admin = $this->model->find($input->id);
        $updateData = [
            'role' => $input->role,
        ];
        $admin->update($updateData);
    }

    public function getAdminById($id)
    {
        return $this->model
            ->when($id, fn ($q) => $q->where('id', '=', $id))
            ->first();
    }

    public function ajaxSearchInforAdmin($search, $role)
    {
        return $this->model->where(function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
        })
            ->where('role', 'like', '%' . $role . '%');
    }

    /**
     * findUserById
     *
     * @param int $id
     * @return object
     */
    public function findAdminById($id)
    {
        return $this->model->find($id);
    }

    /**
     * updateInfor
     *
     * @param object $user
     * @param object $filter
     */
    public function updateInfor($admin, $filter)
    {
        $admin = $this->model->find($admin->id);
        $updateData = [
            'name' => $filter->name,
            'avatar' => $filter->avatar ?? $admin->avatar,
        ];
        $admin->update($updateData);
    }

    /**
     * updatePassword
     *
     * @param object $admin
     * @param srting $password
     */
    public function updatePassword($admin, $password)
    {
        $user = $this->model->find($admin->id);
        $updateData = ['password' => $password];
        $user->update($updateData);
    }

    /**
     * findAdminbyEmail
     *
     * @param string $email
     * @return object
     */
    public function findAdminbyEmail($email)
    {
        return $this->model
            ->when($email, fn ($q) => $q->where('email', '=', $email))
            ->first();
    }
}

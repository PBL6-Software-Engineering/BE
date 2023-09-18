<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository implements UserInterface
{
    public function getModel()
    {
        return User::class;
    }

    /**
     * getUser
     *
     * @param string $emailOrUsername
     * @return object
     */
    public function getUser($emailOrUsername)
    {
        return $this->model
            ->when($emailOrUsername, fn ($q) => $q->where('username', '=', $emailOrUsername))
            ->first();
    }

    /**
     * getAllUser
     *
     * @return array
     */
    public function getAllUser()
    {
        return $this->model;
    }

    /**
     * getUserByUsername
     *
     * @param object $filter
     * @return object
     */
    public function getUserByUsername($filter)
    {
        return $this->model
            ->when($filter->email, fn ($q) => $q->where('email', '!=', $filter->email))
            ->when($filter->username, fn ($q) => $q->where('username', '=', $filter->username))
            ->first();
    }

    /**
     * findUserbyEmail
     *
     * @param string $email
     * @return object
     */
    public function findUserbyEmail($email)
    {
        return $this->model
            ->when($email, fn ($q) => $q->where('email', '=', $email))
            ->first();
    }

    /**
     * updateUser
     *
     * @param object $user
     * @param object $filter
     */
    public function updateUser($user, $filter)
    {
        $user = $this->model->find($user->id);
        $updateData = [
            'password' => $filter->password,
            'avatar' => $filter->avatar,
            'name' => $filter->name,
            'username' => $filter->username,
            'gender' => $filter->gender,
        ];
        $user->update($updateData);
    }

    /**
     * createUser
     *
     * @param object $filter
     * @return object
     */
    public function createUser($filter)
    {
        return $this->model->create([
            'name' => $filter->name,
            'email' => $filter->email,
            'password' => $filter->password,
            'avatar' => $filter->avatar,
            'username' => $filter->username,
            'gender' => $filter->gender,
            'status' => 0,
        ]);
    }

    /**
     * findUserByGithubId
     *
     * @param int $id
     * @return object
     */
    public function findUserByGithubId($id)
    {
        return $this->model
            ->when($id, fn ($q) => $q->where('github_id', '=', $id))
            ->first();
    }

    /**
     * updateIdGithub
     *
     * @param object $user
     * @param int $id
     */
    public function updateIdGithub($user, $id)
    {
        $user = $this->model->find($user->id);
        $updateData = ['github_id' => $id];
        $user->update($updateData);
    }

    /**
     * createUserGithub
     *
     * @param object $user
     * @return object
     */
    public function createUserGithub($user)
    {
        return $this->model->create([
            'name' => $user->name,
            'email' => $user->email,
            'github_id' => $user->id,
            'username' => 'user_' . $user->id,
            'avatar' => $user->avatar,
            'status' => 1,
        ]);
    }

    /**
     * findUserByGoogleId
     *
     * @param int $id
     * @return object
     */
    public function findUserByGoogleId($id)
    {
        return $this->model
            ->when($id, fn ($q) => $q->where('google_id', '=', $id))
            ->first();
    }

    /**
     * updateIdGoogle
     *
     * @param object $user
     * @param int $id
     */
    public function updateIdGoogle($user, $id)
    {
        $user = $this->model->find($user->id);
        $updateData = ['google_id' => $id];
        $user->update($updateData);
    }

    /**
     * createUserGoogle
     *
     * @param object $user
     * @return object
     */
    public function createUserGoogle($user)
    {
        return $this->model->create([
            'name' => $user->name,
            'email' => $user->email,
            'google_id' => $user->id,
            'username' => 'user_' . $user->id,
            'avatar' => $user->avatar,
            'status' => 1,
        ]);
    }

    /**
     * updatePassword
     *
     * @param object $user
     * @param srting $password
     */
    public function updatePassword($user, $password)
    {
        $user = $this->model->find($user->id);
        $updateData = ['password' => $password];
        $user->update($updateData);
    }

    /**
     * findUserById
     *
     * @param int $id
     * @return object
     */
    public static function findUserById($id)
    {
        return (new self)->model->find($id);
    }

    /**
     * updateInfor
     *
     * @param object $user
     * @param object $filter
     */
    public function updateInfor($user, $filter)
    {
        $user = $this->model->find($user->id);
        $updateData = [
            'name' => $filter->name,
            'username' => $filter->username,
            'email' => $filter->email,
            'gender' => $filter->gender,
            'avatar' => $filter->avatar ?? $user->avatar,
        ];
        $user->update($updateData);
    }

    /**
     * searchUser
     *
     * @param string $search_text
     * @return object
     */
    public static function searchUser($search_text)
    {
        return (new self)->model->where('name', 'like', '%' . $search_text . '%')->take(10)->get();
    }

    /**
     * changeStatus
     *
     * @param int $id_user
     */
    public function changeStatus($id_user)
    {
        $user = $this->model::findOrFail($id_user);
        $user->status = $user->status === 1 ? 0 : 1;
        $user->save();

        return $user->status;
    }

    /**
     * ajaxSearchUser
     *
     * @param string $search_text
     * @return array
     */
    public function ajaxSearchUser($search_text)
    {
        return $this->model->where('username', 'like', '%' . $search_text . '%')
            ->orWhere('name', 'like', '%' . $search_text . '%')
            ->orWhere('email', 'like', '%' . $search_text . '%');
    }
}

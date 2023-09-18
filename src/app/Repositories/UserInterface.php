<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface UserInterface extends RepositoryInterface
{
    public function getUser($emailOrUsername);

    public function getUserByUsername($filter);

    public function findUserbyEmail($email);

    public function updateUser($user, $filter);

    public function createUser($filter);

    public function findUserByGoogleId($id);

    public function updateIdGoogle($user, $id);

    public function createUserGoogle($user);

    public function findUserByGithubId($id);

    public function updateIdGithub($user, $id);

    public function createUserGithub($user);

    public function updatePassword($user, $password);

    public function updateInfor($user, $filter);

    public static function findUserById($id);

    public static function searchUser($search_text);

    public function getAllUser();

    public function changeStatus($id_user);

    public function ajaxSearchUser($search_text);
}

<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface AdminInterface extends RepositoryInterface
{
    public function getAllAdmin();

    public function addAdmin($admin);

    public function changeRole($filter);

    public function getAdminById($id);

    public function ajaxSearchInforAdmin($search, $role);

    public function findAdminById($id);

    public function updateInfor($admin, $filter);

    public function updatePassword($admin, $password);

    public function findAdminbyEmail($email);
}

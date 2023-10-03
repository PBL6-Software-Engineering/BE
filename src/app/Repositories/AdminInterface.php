<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface AdminInterface extends RepositoryInterface
{
    public function getAdmin();

    public function findAdminByEmail($email);

    public function findAdminById($id);

    public function findAdminByTokenVerifyEmail($token);

    public function createAdmin($data);

    public function updateAdmin($id, $data);
}

<?php

namespace App\Repositories\Users;

interface UsersRepositoryInterface
{
    public function getAll(int $perPage = 15, int $page = 1, string $role = null, string $search = null);
    public function findById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function findByEmail(string $email);
    public function deleteAll();
    public function deleteAllExcept(array $ids);
    public function deleteSome(array $ids);
    public function import(array $people);
}

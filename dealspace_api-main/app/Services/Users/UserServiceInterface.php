<?php

namespace App\Services\Users;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface UserServiceInterface
{
    public function getAll(int $perPage = 15, int $page = 1, string $role = null, string $search = null);
    public function findById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function bulkDelete(array $params): int;
    public function findUserByEmail(string $email);

    public function importExcel(UploadedFile $file): array;
    public function downloadExcelTemplate(): BinaryFileResponse;
    public function bulkExport(array $params): BinaryFileResponse;
}

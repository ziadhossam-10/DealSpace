<?php

namespace App\Repositories\Emails;

use App\Models\Email;

interface EmailsRepositoryInterface
{
    public function getAll(int $personId, int $perPage = 15, int $page = 1): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function findById(int $id): ?Email;

    public function create(array $data): Email;
}

<?php

namespace App\Repositories\Calls;

use App\Models\Call;

interface CallsRepositoryInterface
{
    public function getAll(int $perPage = 15, int $page = 1, int $personId): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function findById(int $callId): ?Call;

    public function create(array $data): Call;

    public function update(Call $call, array $data): Call;

    public function delete(Call $call): bool;
}

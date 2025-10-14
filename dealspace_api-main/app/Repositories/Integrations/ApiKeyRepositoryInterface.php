<?php

namespace App\Repositories\Integrations;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface ApiKeyRepositoryInterface
{
    public function findByKey(string $key): ?ApiKey;
    public function getUserApiKeys(): LengthAwarePaginator;
    public function create(array $data): ApiKey;
    public function update(ApiKey $apiKey, array $data): ApiKey;
    public function delete(ApiKey $apiKey): bool;
    public function deactivate(ApiKey $apiKey): ApiKey;
    public function activate(ApiKey $apiKey): ApiKey;
}
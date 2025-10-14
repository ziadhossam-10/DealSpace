<?php

namespace App\Repositories\Integrations;

use App\Models\ApiKey;
use App\Models\User;
use App\Repositories\Integrations\ApiKeyRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiKeyRepository implements ApiKeyRepositoryInterface
{
    public function findByKey(string $key): ?ApiKey
    {
        return ApiKey::where('key', $key)
            ->where('is_active', true)
            ->with('user')
            ->first();
    }

    public function getUserApiKeys(): LengthAwarePaginator
    {
        return ApiKey::orderBy('created_at', 'desc')
            ->paginate(15);
    }

    public function create(array $data): ApiKey
    {
        return ApiKey::create($data);
    }

    public function update(ApiKey $apiKey, array $data): ApiKey
    {
        $apiKey->update($data);
        return $apiKey->fresh();
    }

    public function delete(ApiKey $apiKey): bool
    {
        return $apiKey->delete();
    }

    public function deactivate(ApiKey $apiKey): ApiKey
    {
        return $this->update($apiKey, ['is_active' => false]);
    }

    public function activate(ApiKey $apiKey): ApiKey
    {
        return $this->update($apiKey, ['is_active' => true]);
    }
}

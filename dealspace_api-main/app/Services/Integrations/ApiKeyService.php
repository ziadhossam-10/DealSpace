<?php

namespace App\Services\Integrations;

use App\Models\ApiKey;
use App\Models\User;
use App\Repositories\Integrations\ApiKeyRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApiKeyService
{
    public function __construct(
        private ApiKeyRepositoryInterface $apiKeyRepository
    ) {}

    public function generateApiKey(array $data): ApiKey
    {
        $this->validateApiKeyData($data);

        $apiKeyData = [
            'name' => $data['name'],
            'key' => $this->generateUniqueKey(),
            'allowed_domains' => $data['allowed_domains'] ?? null,
            'allowed_endpoints' => $data['allowed_endpoints'] ?? null,
            'is_active' => true,
        ];

        return $this->apiKeyRepository->create($apiKeyData);
    }

    public function updateApiKey(ApiKey $apiKey, array $data): ApiKey
    {
        $this->validateApiKeyData($data, $apiKey);

        $updateData = [
            'name' => $data['name'],
            'allowed_domains' => $data['allowed_domains'] ?? null,
            'allowed_endpoints' => $data['allowed_endpoints'] ?? null,
        ];

        return $this->apiKeyRepository->update($apiKey, $updateData);
    }

    public function validateApiKeyAccess(ApiKey $apiKey, string $domain, string $endpoint): bool
    {
        if (!$apiKey->is_active) {
            return false;
        }

        if (!$apiKey->isDomainAllowed($domain)) {
            return false;
        }

        if (!$apiKey->isEndpointAllowed($endpoint)) {
            return false;
        }

        // Update last used timestamp
        $apiKey->updateLastUsed();

        return true;
    }

    public function revokeApiKey(ApiKey $apiKey): ApiKey
    {
        return $this->apiKeyRepository->deactivate($apiKey);
    }

    public function activateApiKey(ApiKey $apiKey): ApiKey
    {
        return $this->apiKeyRepository->activate($apiKey);
    }

    public function regenerateApiKey(ApiKey $apiKey): ApiKey
    {
        $newKey = $this->generateUniqueKey();
        return $this->apiKeyRepository->update($apiKey, ['key' => $newKey]);
    }

    private function generateUniqueKey(): string
    {
        do {
            $key = 'ak_' . Str::random(40); // API key with prefix
        } while (ApiKey::where('key', $key)->exists());

        return $key;
    }

    private function validateApiKeyData(array $data, ?ApiKey $existingKey = null): void
    {
        if (empty($data['name'])) {
            throw ValidationException::withMessages([
                'name' => ['API key name is required.']
            ]);
        }

        if (isset($data['allowed_domains']) && !empty($data['allowed_domains'])) {
            foreach ($data['allowed_domains'] as $domain) {
                if (!$this->isValidDomain($domain)) {
                    throw ValidationException::withMessages([
                        'allowed_domains' => ["Invalid domain format: {$domain}"]
                    ]);
                }
            }
        }
    }

    private function isValidDomain(string $domain): bool
    {
        return $domain === '*' || filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    }
}

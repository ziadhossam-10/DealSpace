<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiKeys\CreateApiKeyRequest;
use App\Http\Requests\ApiKeys\UpdateApiKeyRequest;
use App\Http\Resources\ApiKeyCollection;
use App\Http\Resources\ApiKeyResource;
use App\Models\ApiKey;
use App\Repositories\Integrations\ApiKeyRepositoryInterface;
use App\Services\Integrations\ApiKeyService;
use Illuminate\Http\JsonResponse;

class ApiKeyController extends Controller
{
    protected $apiKeyService;
    protected $apiKeyRepository;

    public function __construct(
        ApiKeyService $apiKeyService,
        ApiKeyRepositoryInterface $apiKeyRepository
    ) {
        $this->apiKeyService = $apiKeyService;
        $this->apiKeyRepository = $apiKeyRepository;
    }

    /**
     * Get all API keys for the authenticated user.
     *
     * @return JsonResponse JSON response containing all user's API keys.
     */
    public function index(): JsonResponse
    {
        $apiKeys = $this->apiKeyRepository->getUserApiKeys();

        return successResponse(
            'API keys retrieved successfully',
            new ApiKeyCollection($apiKeys)
        );
    }

    /**
     * Get a specific API key by ID.
     *
     * @param ApiKey $apiKey The API key to retrieve.
     * @return JsonResponse JSON response containing the API key.
     */
    public function show(ApiKey $apiKey): JsonResponse
    {
        return successResponse(
            'API key retrieved successfully',
            new ApiKeyResource($apiKey)
        );
    }

    /**
     * Create a new API key.
     *
     * @param CreateApiKeyRequest $request The request instance containing the data to create an API key.
     * @return JsonResponse JSON response containing the created API key and a 201 status code.
     */
    public function store(CreateApiKeyRequest $request): JsonResponse
    {
        $apiKey = $this->apiKeyService->generateApiKey($request->validated());


        return successResponse(
            'API key created successfully',
            array_merge(
                (new ApiKeyResource($apiKey))->toArray($request),
                ['key' => $apiKey->key] // Show key only on creation
            ),
            201
        );
    }

    /**
     * Update an existing API key.
     *
     * @param UpdateApiKeyRequest $request The request instance containing the data to update.
     * @param ApiKey $apiKey The API key to update.
     * @return JsonResponse JSON response containing the updated API key.
     */
    public function update(UpdateApiKeyRequest $request, ApiKey $apiKey): JsonResponse
    {
        $updatedApiKey = $this->apiKeyService->updateApiKey($apiKey, $request->validated());

        return successResponse(
            'API key updated successfully',
            new ApiKeyResource($updatedApiKey)
        );
    }

    /**
     * Delete an API key.
     *
     * @param ApiKey $apiKey The API key to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(ApiKey $apiKey): JsonResponse
    {
        $this->apiKeyRepository->delete($apiKey);

        return successResponse(
            'API key deleted successfully',
            null
        );
    }

    /**
     * Revoke an API key.
     *
     * @param ApiKey $apiKey The API key to revoke.
     * @return JsonResponse JSON response indicating the result of the revocation.
     */
    public function revoke(ApiKey $apiKey): JsonResponse
    {
        $this->apiKeyService->revokeApiKey($apiKey);

        return successResponse(
            'API key revoked successfully',
            new ApiKeyResource($apiKey->fresh())
        );
    }

    /**
     * Activate an API key.
     *
     * @param ApiKey $apiKey The API key to activate.
     * @return JsonResponse JSON response indicating the result of the activation.
     */
    public function activate(ApiKey $apiKey): JsonResponse
    {
        $activatedApiKey = $this->apiKeyService->activateApiKey($apiKey);

        return successResponse(
            'API key activated successfully',
            new ApiKeyResource($activatedApiKey)
        );
    }

    /**
     * Regenerate an API key.
     *
     * @param ApiKey $apiKey The API key to regenerate.
     * @return JsonResponse JSON response containing the regenerated API key with new key.
     */
    public function regenerate(ApiKey $apiKey): JsonResponse
    {
        $updatedApiKey = $this->apiKeyService->regenerateApiKey($apiKey);

        return successResponse(
            'API key regenerated successfully',
            array_merge(
                (new ApiKeyResource($updatedApiKey))->toArray(request()),
                ['key' => $updatedApiKey->key] // Show new key
            )
        );
    }
}

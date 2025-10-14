<?php

namespace App\Http\Middleware;

use App\Services\Integrations\ApiKeyService;
use App\Repositories\Integrations\ApiKeyRepositoryInterface;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApiKeyAuth
{
    public function __construct(
        private ApiKeyRepositoryInterface $apiKeyRepository,
        private ApiKeyService $apiKeyService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $apiKey = $this->getApiKeyFromRequest($request);

        if (!$apiKey) {
            return response()->json(['error' => 'API key required'], 401);
        }

        $apiKeyModel = $this->apiKeyRepository->findByKey($apiKey);

        if (!$apiKeyModel) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        $domain = $request->getHost();
        $endpoint = $request->path();

        if (!$this->apiKeyService->validateApiKeyAccess($apiKeyModel, $domain, $endpoint)) {
            return response()->json(['error' => 'API key access denied'], 403);
        }

        // Set the authenticated user

        $tenant = tenancy()->find($apiKeyModel->tenant_id);

        if (!$tenant) {
            throw new AuthenticationException("Tenant not found for user.");
        }

        tenancy()->initialize($tenant);

        $request->merge(['api_key_model' => $apiKeyModel]);

        return $next($request);
    }

    private function getApiKeyFromRequest(Request $request): ?string
    {
        // Check for API key in header
        if ($request->hasHeader('X-API-Key')) {
            return $request->header('X-API-Key');
        }

        // Check for API key in query parameter
        if ($request->has('api_key')) {
            return $request->get('api_key');
        }

        return null;
    }
}

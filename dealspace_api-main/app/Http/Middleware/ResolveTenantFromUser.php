<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Stancl\Tenancy\Tenancy;

class ResolveTenantFromUser
{
    protected $tenancy;

    public function __construct(Tenancy $tenancy)
    {
        $this->tenancy = $tenancy;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get authenticated user
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Check if user has a tenant_id
        if (!$user->tenant_id) {
            return response()->json([
                'message' => 'User does not belong to a tenant.'
            ], 403);
        }

        // Initialize tenant context
        try {
            $tenant = \App\Models\Tenant::find($user->tenant_id);
            
            if (!$tenant) {
                return response()->json([
                    'message' => 'Tenant not found.'
                ], 404);
            }

            // Initialize the tenant context
            $this->tenancy->initialize($tenant);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to initialize tenant context.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }

        return $next($request);
    }
}

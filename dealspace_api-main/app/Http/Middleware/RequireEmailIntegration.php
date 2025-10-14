<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireEmailIntegration
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user->hasEmailIntegration()) {
            return response()->json([
                'error' => 'Email integration required',
                'message' => 'You need to connect your email account to use this feature'
            ], 403);
        }

        return $next($request);
    }
}

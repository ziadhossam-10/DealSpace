<?php

use App\Helpers\ApiResponder;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

$apiResponder = new ApiResponder();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo('/api/auth/not-authenticated');
        
        // Exclude Stripe webhook from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
        ]);
        
        // Register middleware aliases
        $middleware->alias([
            'subscribed' => \App\Http\Middleware\CheckSubscription::class,
            'tenant' => \App\Http\Middleware\ResolveTenantFromUser::class,
            'check.subscription' => \App\Http\Middleware\CheckSubscription::class,
            'api.key' => \App\Http\Middleware\ApiKeyAuth::class,
            'require.email' => \App\Http\Middleware\RequireEmailIntegration::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) use ($apiResponder) {
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($apiResponder) {
            if ($e instanceof ValidationException) {
                return $apiResponder->validationErrorResponse(
                    $e->errors(),
                    'Validation failed',
                    422
                );
            }

            if ($e instanceof AuthenticationException) {
                return $apiResponder->errorResponse($e->getMessage() ?? 'Unauthenticated', 401);
            }

            if ($e instanceof ModelNotFoundException) {
                $model = strtolower(class_basename($e->getModel()));
                return $apiResponder->errorResponse($e->getMessage() ??  "{$model} not found", 404);
            }

            if ($e instanceof NotFoundHttpException) {
                return $apiResponder->errorResponse($e->getMessage() ??  'The specified URL cannot be found', 404);
            }

            if ($e instanceof MethodNotAllowedHttpException) {
                return $apiResponder->errorResponse($e->getMessage() ??  'The specified method for the request is invalid', 405);
            }

            if ($e instanceof ThrottleRequestsException) {
                return $apiResponder->errorResponse($e->getMessage() ?? 'Too many requests', 429);
            }

            if ($e instanceof HttpExceptionInterface) {
                return $apiResponder->errorResponse(
                    $e->getMessage() ?: 'HTTP error',
                    $e->getStatusCode()
                );
            }

            if (config('app.debug')) {
                return $apiResponder->errorResponse(
                    $e->getMessage(),
                    500,
                    [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => collect($e->getTrace())->take(5),
                    ]
                );
            }

            return $apiResponder->errorResponse('Unexpected error. Try later', 500);
        });
    })->create();

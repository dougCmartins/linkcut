<?php

declare(strict_types=1);

use App\Http\Middleware\AssignRequestContext;
use Domain\Tenant\Exceptions\TenantMismatchException;
use Domain\Tenant\Exceptions\TenantNotResolvedException;
use Domain\Tenant\Middleware\ResolveTenant;
use Domain\Tenant\Middleware\ResolveTenantFromSlug;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Error envelope (success responses are the Data itself, never wrapped):
 * { data, message, code, status_code, errors, request_id }
 */
$envelope = static function (Request $request, string $message, string $code, int $status, array $errors = [], ?array $data = null): JsonResponse {
    return response()->json([
        'data' => $data,
        'message' => $message,
        'code' => $code,
        'status_code' => $status,
        'errors' => $errors,
        'request_id' => $request->attributes->get(AssignRequestContext::ATTRIBUTE),
    ], $status);
};

$isApi = static fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [AssignRequestContext::class]);
        // Behind a reverse proxy (Caddy in production, Vite in development): trust its X-Forwarded-* headers, so the
        // app sees https and the visitor's real IP (signed links, rate limits). The app port is never public in production.
        $middleware->trustProxies(at: '*');
        $middleware->alias(['tenant' => ResolveTenant::class, 'tenant.slug' => ResolveTenantFromSlug::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) use ($envelope, $isApi): void {
        // Expected domain errors (wrong code, expired session…) are answers, not incidents: keep them out
        // of Sentry and the error log. Tenant isolation errors are always reported (bug or attack).
        // Registered before Sentry, so returning false stops the report there too.
        $exceptions->report(function (DomainException $exception) {
            $isTenantIsolationError = $exception instanceof TenantNotResolvedException || $exception instanceof TenantMismatchException;
            if ($isTenantIsolationError) {
                Log::channel('security')->error('tenant_isolation_error', ['code' => $exception->getErrorCode()]);

                return null;
            }

            return method_exists($exception, 'getHttpStatus') && $exception->getHttpStatus() < 500 ? false : null;
        });

        Integration::handles($exceptions);

        $exceptions->render(function (ValidationException $exception, Request $request) use ($envelope, $isApi) {
            if (! $isApi($request)) {
                return null;
            }
            // Each field gets the snake_case name of the rule that failed ("required", "after_or_equal"…), translated by the SPA.
            // Rule objects: Laravel's (Enum…) use their class name, ours fail with their own code ("phone").
            $errors = collect($exception->validator->failed())
                ->map(function (array $rules, string $field) use ($exception): array {
                    $rule = (string) array_key_first($rules);
                    if (class_exists($rule) && ! str_starts_with($rule, 'Illuminate\\')) {
                        return [(string) $exception->errors()[$field][0]];
                    }

                    return [Str::snake(class_basename($rule))];
                })
                ->all();
            // Rules checked inside an Action (ValidationException::withMessages) carry the rule name as message.
            if (empty($errors)) {
                $errors = collect($exception->errors())->map(fn (array $messages): array => [(string) $messages[0]])->all();
            }

            return $envelope($request, $exception->getMessage(), 'VALIDATION_FAILED', 422, $errors);
        });

        $exceptions->render(function (DomainException $exception, Request $request) use ($envelope, $isApi) {
            if (! $isApi($request) || ! method_exists($exception, 'getErrorCode') || ! method_exists($exception, 'getHttpStatus')) {
                return null;
            }
            $data = method_exists($exception, 'getErrorData') ? $exception->getErrorData() : null;

            return $envelope($request, $exception->getMessage(), $exception->getErrorCode(), $exception->getHttpStatus(), [], $data);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($envelope, $isApi) {
            return $isApi($request) ? $envelope($request, 'Unauthenticated.', 'UNAUTHENTICATED', 401) : null;
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) use ($envelope, $isApi) {
            return $isApi($request)
                ? $envelope($request, 'Too many requests. Try again in a moment.', 'TOO_MANY_REQUESTS', 429)->withHeaders($exception->getHeaders())
                : null;
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($envelope, $isApi) {
            if (! $isApi($request)) {
                return null;
            }
            $status = $exception->getStatusCode();

            return $envelope($request, $status === 404 ? 'Not found.' : 'Request not allowed.', $status === 404 ? 'NOT_FOUND' : 'HTTP_ERROR', $status);
        });

        // Anything else: a generic 500 envelope (details only in the logs and Sentry, unless debugging).
        $exceptions->render(function (Throwable $exception, Request $request) use ($envelope, $isApi) {
            if (! $isApi($request) || config('app.debug')) {
                return null;
            }

            return $envelope($request, 'Something went wrong. Try again in a moment.', 'SERVER_ERROR', 500);
        });
    })->create();

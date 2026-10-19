<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Sentry\State\Scope;
use Symfony\Component\HttpFoundation\Response;

use function Sentry\configureScope;

/**
 * Gives every API request an id (X-Request-Id, kept if the SPA sent a valid one),
 * puts it in every log line, in Sentry and in the response, so an error the barber
 * sees can be found in the logs.
 */
final class AssignRequestContext
{
    public const ATTRIBUTE = 'request_id';

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = (string) $request->headers->get('X-Request-Id');
        $requestId = preg_match('/^[A-Za-z0-9-]{8,64}$/', $incoming) ? $incoming : (string) Str::uuid();

        $request->attributes->set(self::ATTRIBUTE, $requestId);
        Log::shareContext(['request_id' => $requestId]);
        configureScope(fn (Scope $scope) => $scope->setTag('request_id', $requestId));

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}

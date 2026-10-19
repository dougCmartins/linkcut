<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * /pulse is open on a developer machine. Anywhere else it asks for HTTP Basic Auth
 * (PULSE_USER / PULSE_PASSWORD_HASH): the SPA uses Bearer tokens, so there is no web session to rely on.
 */
final class AuthorizePulse
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            return $next($request);
        }

        $user = (string) config('pulse.basic_auth.user');
        $passwordHash = (string) config('pulse.basic_auth.password_hash');
        $authorized = $user && $passwordHash
            && hash_equals($user, (string) $request->getUser())
            && Hash::check((string) $request->getPassword(), $passwordHash);

        if (! $authorized) {
            return response('Unauthorized.', 401, ['WWW-Authenticate' => 'Basic realm="Pulse"']);
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace Domain\Identity\Middleware;

use Closure;
use Domain\Identity\Enums\UserRole;
use Domain\Identity\Exceptions\OwnerOnlyException;
use Domain\Identity\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "role.owner", after auth:api: only the owner (the role is checked here, not trusted from the screen).
 * Barbers of the team get 403 OWNER_ONLY on finance, settings, products, reviews, account and team management.
 */
final class EnsureOwnerRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || $user->role !== UserRole::Owner) {
            throw new OwnerOnlyException;
        }

        return $next($request);
    }
}

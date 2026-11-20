<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Team\Actions\FindStaffPermissionsForUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Barbers without see_prices must not receive money fields on API responses (decision 0001 P3).
 * Lives in App (not a domain) so it can call Team without breaking ArchitectureTest.
 */
final class RedactMoneyForStaff
{
    public function __construct(private readonly FindStaffPermissionsForUser $permissions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if (! $user instanceof User || $user->role !== UserRole::Barber) {
            return $response;
        }
        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $perms = $this->permissions->handle($user->id);
        if ($perms?->see_prices) {
            return $response;
        }

        $payload = $response->getData(true);
        if (! is_array($payload)) {
            return $response;
        }

        $response->setData($this->redact($payload));

        return $response;
    }

    /** @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function redact(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isMoneyKey($key)) {
                $out[$key] = null;

                continue;
            }
            $out[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $out;
    }

    private function isMoneyKey(string $key): bool
    {
        $key = strtolower($key);

        return str_ends_with($key, '_in_cents')
            || str_contains($key, 'price')
            || str_contains($key, 'revenue')
            || str_contains($key, 'cost')
            || str_contains($key, 'total')
            || str_contains($key, 'amount')
            || str_contains($key, 'commission');
    }
}

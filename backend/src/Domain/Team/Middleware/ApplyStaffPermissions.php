<?php

declare(strict_types=1);

namespace Domain\Team\Middleware;

use BackedEnum;
use Closure;
use Domain\Team\Actions\ShowPermissionsOfUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * On every API answer to a signed-in barber of the team (role barber; the owner is untouched): without "see_prices",
 * no value leaves the API: every "*_in_cents" key is removed from the JSON, whatever the endpoint (a check on the
 * answer, not only on the screen — docs/decisoes/0001 P3). It only reads the answer; it never blocks a call.
 */
final class ApplyStaffPermissions
{
    private const BARBER_ROLE = 'barber';

    public function __construct(private readonly ShowPermissionsOfUser $showPermissions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $guard = Auth::guard('api');
        if (! $guard->hasUser()) {
            return $response;
        }
        $user = $guard->user();
        $role = $user?->getAttribute('role');
        if (($role instanceof BackedEnum ? $role->value : $role) !== self::BARBER_ROLE) {
            return $response;
        }

        if ($response instanceof JsonResponse && ! $this->showPermissions->handle((int) $user->getAuthIdentifier())->see_prices) {
            $response->setData($this->withoutValues($response->getData(true)));
        }

        return $response;
    }

    private function withoutValues(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && str_ends_with($key, '_in_cents')) {
                continue;
            }
            $clean[$key] = $this->withoutValues($value);
        }

        return $clean;
    }
}

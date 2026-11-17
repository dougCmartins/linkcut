<?php

declare(strict_types=1);

namespace Domain\Tenant\Middleware;

use Closure;
use Domain\Tenant\Actions\ResolveActiveUnit;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\UnitAccessData;
use Domain\Tenant\Exceptions\TenantNotResolvedException;
use Domain\Tenant\Exceptions\UnitForbiddenException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs after auth:api. Makes the unit the request works on the current tenant, after checking it is active:
 * the user's own unit, or the unit picked at the top of the dashboard (X-Unit header), which only the owner of the
 * organization may open, and only among its units (docs/adr/0002).
 */
final class ResolveTenant
{
    public const UNIT_HEADER = 'X-Unit';

    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly ResolveActiveUnit $resolveActiveUnit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenantId = $user?->getAttribute('tenant_id');
        if (! $tenantId) {
            throw new TenantNotResolvedException;
        }

        $organizationId = $user->getAttribute('organization_id');
        $unit = $this->resolveActiveUnit->handle(new UnitAccessData(
            user_id: (int) $user->getAuthIdentifier(),
            home_tenant_id: (int) $tenantId,
            organization_id: $organizationId === null ? null : (int) $organizationId,
            unit_id: $this->pickedUnit($request),
        ));
        $this->currentTenant->set($unit->id, $unit->timezone);

        return $next($request);
    }

    private function pickedUnit(Request $request): ?int
    {
        $header = $request->header(self::UNIT_HEADER);
        if ($header === null || $header === '') {
            return null;
        }
        if (! ctype_digit($header)) {
            throw new UnitForbiddenException;
        }

        return (int) $header;
    }
}

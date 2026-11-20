<?php

declare(strict_types=1);

namespace Domain\Tenant\Middleware;

use Closure;
use Domain\Tenant\Actions\EnsureTenantIsActive;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\TenantIdData;
use Domain\Tenant\Exceptions\TenantNotResolvedException;
use Domain\Tenant\Exceptions\UnitForbiddenException;
use Domain\Tenant\Models\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs after auth:api. Resolves the active unit (tenant) for the request:
 * X-Unit when it belongs to the user's organization, otherwise the main unit.
 */
final class ResolveTenant
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly EnsureTenantIsActive $ensureTenantIsActive,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $request->user()?->getAttribute('organization_id');
        if (! $organizationId) {
            throw new TenantNotResolvedException;
        }

        $unitHeader = $request->headers->get('X-Unit');
        if ($unitHeader !== null && $unitHeader !== '') {
            $tenant = Tenant::query()
                ->whereKey((int) $unitHeader)
                ->where('organization_id', (int) $organizationId)
                ->first();
            if (! $tenant) {
                throw new UnitForbiddenException;
            }
        } else {
            $tenant = Tenant::query()
                ->where('organization_id', (int) $organizationId)
                ->where('is_main', true)
                ->first();
            if (! $tenant) {
                throw new TenantNotResolvedException;
            }
        }

        $tenant = $this->ensureTenantIsActive->handle(new TenantIdData(tenant_id: $tenant->id));
        $this->currentTenant->set($tenant->id, $tenant->timezone);

        return $next($request);
    }
}

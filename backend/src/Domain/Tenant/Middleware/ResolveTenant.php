<?php

declare(strict_types=1);

namespace Domain\Tenant\Middleware;

use Closure;
use Domain\Tenant\Actions\EnsureTenantIsActive;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\TenantIdData;
use Domain\Tenant\Exceptions\TenantNotResolvedException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs after auth:api. Takes the tenant of the logged-in user, checks it is active
 * and makes it the current tenant for the rest of the request.
 */
final class ResolveTenant
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly EnsureTenantIsActive $ensureTenantIsActive,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->user()?->getAttribute('tenant_id');
        if (! $tenantId) {
            throw new TenantNotResolvedException;
        }

        $tenant = $this->ensureTenantIsActive->handle(new TenantIdData(tenant_id: (int) $tenantId));
        $this->currentTenant->set($tenant->id);

        return $next($request);
    }
}

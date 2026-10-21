<?php

declare(strict_types=1);

namespace Domain\Tenant\Middleware;

use Closure;
use Domain\Tenant\Actions\FindActiveTenantBySlug;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\TenantSlugData;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Public routes /api/public/{slug}/…: the barbershop of the URL becomes the current tenant. */
final class ResolveTenantFromSlug
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly FindActiveTenantBySlug $findActiveTenantBySlug,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('slug');
        $tenant = $this->findActiveTenantBySlug->handle(TenantSlugData::from(TenantSlugData::validate(['slug' => $slug])));
        $this->currentTenant->set($tenant->id, $tenant->timezone);

        return $next($request);
    }
}

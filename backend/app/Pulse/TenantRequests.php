<?php

declare(strict_types=1);

namespace App\Pulse;

use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Laravel\Pulse\Facades\Pulse;

/**
 * Pulse recorder: counts API requests and server errors per tenant,
 * shown on the "Tenants" card of /pulse.
 */
final class TenantRequests
{
    public const REQUESTS = 'tenant_request';

    public const ERRORS = 'tenant_error';

    /** @var class-string */
    public string $listen = RequestHandled::class;

    public function record(RequestHandled $event): void
    {
        $currentTenant = app(CurrentTenant::class);
        if (! $currentTenant->has()) {
            return;
        }

        $tenantId = (string) $currentTenant->id();
        Pulse::record(self::REQUESTS, $tenantId)->count();
        if ($event->response->getStatusCode() >= 500) {
            Pulse::record(self::ERRORS, $tenantId)->count();
        }
    }
}

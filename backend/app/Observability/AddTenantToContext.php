<?php

declare(strict_types=1);

namespace App\Observability;

use Domain\Tenant\Events\TenantResolved;
use Illuminate\Support\Facades\Log;
use Sentry\State\Scope;

use function Sentry\configureScope;

/** When the tenant is known, every log line and Sentry event of the request carries tenant_id and user_id. */
final class AddTenantToContext
{
    public function handle(TenantResolved $event): void
    {
        $userId = auth('api')->id();
        Log::shareContext(['tenant_id' => $event->tenantId, 'user_id' => $userId]);
        configureScope(function (Scope $scope) use ($event, $userId): void {
            $scope->setTag('tenant_id', (string) $event->tenantId);
            if ($userId) {
                $scope->setUser(['id' => (string) $userId]);
            }
        });
    }
}

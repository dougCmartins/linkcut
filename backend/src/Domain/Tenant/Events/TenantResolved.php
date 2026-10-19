<?php

declare(strict_types=1);

namespace Domain\Tenant\Events;

/** The request now has a tenant (used to tag logs, Sentry and Pulse). */
final readonly class TenantResolved
{
    public function __construct(public int $tenantId) {}
}

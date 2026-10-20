<?php

declare(strict_types=1);

namespace Domain\Tenant\Context;

use Carbon\CarbonImmutable;
use Domain\Tenant\Models\Tenant;

/** "Today" and "now" in the timezone of the current tenant (dates and times are stored in it). */
final class TenantClock
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function now(): CarbonImmutable
    {
        $timezone = Tenant::query()->whereKey($this->currentTenant->id())->value('timezone');

        return CarbonImmutable::now($timezone ?: config('app.timezone'));
    }

    public function today(): CarbonImmutable
    {
        return $this->now()->startOfDay();
    }
}

<?php

declare(strict_types=1);

namespace Domain\Tenant\Context;

use Carbon\CarbonImmutable;
use Domain\Tenant\Models\Tenant;

/** "Today" and "now" in the timezone of the current tenant (dates and times are stored in it). */
final class TenantClock
{
    /** @var array<int, string> timezone per tenant id, read once per request */
    private array $timezones = [];

    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone() ?: config('app.timezone'));
    }

    public function today(): CarbonImmutable
    {
        return $this->now()->startOfDay();
    }

    private function timezone(): string
    {
        $id = $this->currentTenant->id();

        return $this->currentTenant->timezone()
            ?? ($this->timezones[$id] ??= (string) Tenant::query()->whereKey($id)->value('timezone'));
    }
}

<?php

declare(strict_types=1);

namespace Domain\Tenant\Context;

use Closure;
use Domain\Tenant\Events\TenantResolved;
use Domain\Tenant\Exceptions\TenantNotResolvedException;

/**
 * The tenant of the current request (or job). One instance per request ("scoped" in the container).
 * Tenant-owned models read it through the global TenantScope, so nobody filters by tenant by hand.
 */
final class CurrentTenant
{
    private ?int $id = null;

    /** Known when the tenant was resolved from its row (saves TenantClock a query). */
    private ?string $timezone = null;

    public function set(int $id, ?string $timezone = null): void
    {
        $this->id = $id;
        $this->timezone = $timezone;
        event(new TenantResolved($id));
    }

    public function timezone(): ?string
    {
        return $this->timezone;
    }

    public function has(): bool
    {
        return (bool) $this->id;
    }

    /** @throws TenantNotResolvedException when no tenant was resolved (fail closed). */
    public function id(): int
    {
        return $this->id ?? throw new TenantNotResolvedException;
    }

    public function forget(): void
    {
        $this->id = null;
        $this->timezone = null;
    }

    /**
     * Runs a callback inside a tenant (jobs, commands) and restores the previous context.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(int $id, Closure $callback): mixed
    {
        [$previousId, $previousTimezone] = [$this->id, $this->timezone];
        $this->id = $id;
        $this->timezone = null;
        try {
            return $callback();
        } finally {
            [$this->id, $this->timezone] = [$previousId, $previousTimezone];
        }
    }
}

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

    public function set(int $id): void
    {
        $this->id = $id;
        event(new TenantResolved($id));
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
        $previous = $this->id;
        $this->id = $id;
        try {
            return $callback();
        } finally {
            $this->id = $previous;
        }
    }
}

<?php

declare(strict_types=1);

namespace Domain\Tenant\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cached public brand page (list of active units for an organization).
 * Forget when units or a unit's public profile change.
 */
final class PublicBrandCache
{
    private const TTL_SECONDS = 600;

    /**
     * @template T
     *
     * @param  Closure(): T  $build
     * @return T
     */
    public function remember(int $organizationId, Closure $build): mixed
    {
        return Cache::remember($this->key($organizationId), self::TTL_SECONDS, $build);
    }

    public function forget(int $organizationId): void
    {
        Cache::forget($this->key($organizationId));
    }

    private function key(int $organizationId): string
    {
        return "brand:{$organizationId}";
    }
}

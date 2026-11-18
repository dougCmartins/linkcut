<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicPage\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache of the brand link: its units (by brand slug) and whether a unit's page is a brand link (by tenant).
 * Creating a unit forgets both; a unit's name, photo or address change shows up within the TTL.
 */
final class BrandCache
{
    private const TTL_SECONDS = 300;

    /**
     * @template T
     *
     * @param  Closure(): T  $build
     * @return T
     */
    public function units(string $brandSlug, Closure $build): mixed
    {
        return Cache::remember("brand:{$brandSlug}:units", self::TTL_SECONDS, $build);
    }

    /** @param  Closure(): ?string  $build */
    public function brandOf(int $tenantId, Closure $build): ?string
    {
        // Cached as '' when the page is no brand link (null is not cached by remember).
        $slug = Cache::remember("tenant:{$tenantId}:brand", self::TTL_SECONDS, fn (): string => $build() ?? '');

        return $slug === '' ? null : $slug;
    }

    public function forget(string $brandSlug, int $mainTenantId): void
    {
        Cache::deleteMultiple(["brand:{$brandSlug}:units", "tenant:{$mainTenantId}:brand"]);
    }
}

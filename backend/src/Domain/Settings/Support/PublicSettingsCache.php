<?php

declare(strict_types=1);

namespace Domain\Settings\Support;

use Closure;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Support\Facades\Cache;

/**
 * What visitors read from the settings (public page, booking options), cached per tenant. Saving any settings tab
 * forgets it; the TTL only bounds how stale it can get after a direct database change (e.g. a data migration).
 */
final class PublicSettingsCache
{
    public const PUBLIC_PAGE = 'public-page';

    public const BOOKING_OPTIONS = 'booking-options';

    public const REVIEWS = 'reviews';

    private const TTL_SECONDS = 600;

    public function __construct(private readonly CurrentTenant $tenant) {}

    /**
     * @template T
     *
     * @param  Closure(): T  $build
     * @return T
     */
    public function remember(string $part, Closure $build): mixed
    {
        return Cache::remember($this->key($part), self::TTL_SECONDS, $build);
    }

    public function forget(): void
    {
        Cache::deleteMultiple([
            $this->key(self::PUBLIC_PAGE),
            $this->key(self::BOOKING_OPTIONS),
            $this->key(self::REVIEWS),
        ]);
    }

    private function key(string $part): string
    {
        return "settings:{$this->tenant->id()}:{$part}";
    }
}

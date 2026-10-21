<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\SlugAvailabilityData;
use Domain\Tenant\Data\TenantSlugData;
use Domain\Tenant\Models\Tenant;
use Domain\Tenant\Support\ReservedSlugs;

/** "linkcut.app/{slug}" is free? When not, suggests slug-2, slug-3… */
final class CheckSlugAvailability
{
    private const MAX_SUFFIX = 99;

    public function handle(TenantSlugData $data): SlugAvailabilityData
    {
        $available = self::isFree($data->slug);
        $suggestion = $data->slug;
        for ($suffix = 2; ! $available && $suffix <= self::MAX_SUFFIX && ! self::isFree($suggestion); $suffix++) {
            $suggestion = "{$data->slug}-{$suffix}";
        }

        return new SlugAvailabilityData(slug: $data->slug, available: $available, suggestion: $suggestion);
    }

    public static function isFree(string $slug): bool
    {
        return ! ReservedSlugs::contains($slug) && ! Tenant::query()->where('slug', $slug)->exists();
    }
}

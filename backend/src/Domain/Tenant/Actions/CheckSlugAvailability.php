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

    /** One query: the slug and every "<slug>-<n>" already taken; the suggestion is picked in memory. */
    public function handle(TenantSlugData $data): SlugAvailabilityData
    {
        $slug = $data->slug;
        $taken = array_flip(Tenant::query()
            ->where('slug', $slug)
            ->orWhere('slug', 'like', addcslashes($slug, '%_\\').'-%')
            ->pluck('slug')
            ->all());
        $isFree = fn (string $candidate): bool => ! isset($taken[$candidate]) && ! ReservedSlugs::contains($candidate);

        $available = $isFree($slug);
        $suggestion = $slug;
        for ($suffix = 2; ! $available && $suffix <= self::MAX_SUFFIX; $suffix++) {
            $suggestion = "{$slug}-{$suffix}";
            if ($isFree($suggestion)) {
                break;
            }
        }

        return new SlugAvailabilityData(slug: $slug, available: $available, suggestion: $suggestion);
    }

    public static function isFree(string $slug): bool
    {
        return ! ReservedSlugs::contains($slug) && ! Tenant::query()->where('slug', $slug)->exists();
    }
}

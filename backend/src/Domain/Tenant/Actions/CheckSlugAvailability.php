<?php

declare(strict_types=1);

namespace Domain\Tenant\Actions;

use Domain\Tenant\Data\SlugAvailabilityData;
use Domain\Tenant\Data\TenantSlugData;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Domain\Tenant\Support\ReservedSlugs;
use Illuminate\Support\Facades\DB;

/** "linkcut.app/{slug}" is free? When not, suggests slug-2, slug-3… Checks tenants and organizations. */
final class CheckSlugAvailability
{
    private const MAX_SUFFIX = 99;

    /** One query: the slug and every "<slug>-<n>" already taken in tenants or organizations. */
    public function handle(TenantSlugData $data): SlugAvailabilityData
    {
        $slug = $data->slug;
        $like = addcslashes($slug, '%_\\').'-%';
        $taken = array_flip(DB::query()
            ->fromSub(
                Tenant::query()->select('slug')->where('slug', $slug)->orWhere('slug', 'like', $like)
                    ->unionAll(Organization::query()->select('slug')->where('slug', $slug)->orWhere('slug', 'like', $like)),
                'taken_slugs',
            )
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

    /**
     * @param  int|null  $allowOrganizationId  Organization that may keep this slug (main unit shares the brand slug).
     */
    public static function isFree(string $slug, ?int $allowOrganizationId = null): bool
    {
        if (ReservedSlugs::contains($slug)) {
            return false;
        }
        if (Tenant::query()->where('slug', $slug)->exists()) {
            return false;
        }
        $organizations = Organization::query()->where('slug', $slug);
        if ($allowOrganizationId !== null) {
            $organizations->whereKeyNot($allowOrganizationId);
        }

        return ! $organizations->exists();
    }
}

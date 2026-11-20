<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBrand\Actions;

use Domain\Orchestrator\PublicBrand\Data\BrandUnitData;
use Domain\Orchestrator\PublicBrand\Data\PublicBrandData;
use Domain\Settings\Models\Image;
use Domain\Settings\Models\ProfileSettings;
use Domain\Tenant\Exceptions\OrganizationNotFoundException;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Domain\Tenant\Support\PublicBrandCache;
use Illuminate\Support\Facades\Storage;

/** Lists the active units of a brand (organization slug) for the public unit select. */
final class ShowPublicBrand
{
    public function __construct(private readonly PublicBrandCache $cache) {}

    public function handle(string $slug): PublicBrandData
    {
        $organization = Organization::query()->where('slug', $slug)->first();
        if ($organization === null) {
            throw new OrganizationNotFoundException;
        }

        return $this->cache->remember($organization->id, fn (): PublicBrandData => $this->build($organization));
    }

    private function build(Organization $organization): PublicBrandData
    {
        $units = Tenant::query()
            ->where('organization_id', $organization->id)
            ->where('active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'is_main']);

        $tenantIds = $units->pluck('id')->all();
        $profiles = ProfileSettings::withoutTenantScope()
            ->whereIn('tenant_id', $tenantIds)
            ->get(['tenant_id', 'name', 'avatar_image_id', 'address_zip_code', 'address_street', 'address_number',
                'address_complement', 'address_district', 'address_city', 'address_state', 'latitude', 'longitude'])
            ->keyBy('tenant_id');

        $avatarIds = $profiles->pluck('avatar_image_id')->filter()->unique()->values()->all();
        $avatars = $avatarIds === []
            ? collect()
            : Image::withoutTenantScope()
                ->whereKey($avatarIds)
                ->get(['id', 'path'])
                ->keyBy('id');

        $unitData = $units->map(function (Tenant $tenant) use ($profiles, $avatars): BrandUnitData {
            /** @var ProfileSettings|null $profile */
            $profile = $profiles->get($tenant->id);
            $avatar = $profile?->avatar_image_id ? $avatars->get($profile->avatar_image_id) : null;

            return new BrandUnitData(
                name: $profile?->name ?: $tenant->name,
                slug: $tenant->slug,
                is_main: $tenant->is_main,
                address: $profile?->address(),
                photo_url: $avatar !== null ? Storage::disk(Image::DISK)->url($avatar->path) : null,
            );
        })->values()->all();

        return new PublicBrandData(
            name: $organization->name,
            slug: $organization->slug,
            units: $unitData,
        );
    }
}

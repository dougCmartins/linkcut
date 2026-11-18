<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicPage\Actions;

use Domain\Orchestrator\PublicPage\Data\PublicBrandData;
use Domain\Orchestrator\PublicPage\Data\PublicBrandUnitData;
use Domain\Orchestrator\PublicPage\Support\BrandCache;
use Domain\Settings\Actions\ListContactProfiles;
use Domain\Settings\Data\ContactProfileData;
use Domain\Tenant\Actions\ShowBrand;
use Domain\Tenant\Data\BrandUnitData;
use Domain\Tenant\Data\TenantIdsData;
use Domain\Tenant\Data\TenantSlugData;

/**
 * The brand link's picker (docs/decisoes/0001 P2): the active units (Tenant) with their name, photo and address
 * (Settings), cached per brand. The same few queries for 2 or 20 units.
 */
final class ShowPublicBrand
{
    public function __construct(
        private readonly ShowBrand $showBrand,
        private readonly ListContactProfiles $listProfiles,
        private readonly BrandCache $cache,
    ) {}

    public function handle(TenantSlugData $data): PublicBrandData
    {
        return $this->cache->units($data->slug, function () use ($data): PublicBrandData {
            $brand = $this->showBrand->handle($data);
            $ids = array_map(fn (BrandUnitData $unit): int => $unit->tenant_id, $brand->units);
            $profiles = collect($this->listProfiles->handle(new TenantIdsData(ids: $ids))->all())
                ->keyBy(fn (ContactProfileData $profile): int => $profile->tenant_id);

            return new PublicBrandData(
                slug: $brand->slug,
                name: $brand->name,
                units: array_map(fn (BrandUnitData $unit): PublicBrandUnitData => new PublicBrandUnitData(
                    slug: $unit->slug,
                    name: $profiles->get($unit->tenant_id)?->name ?? $unit->name,
                    is_main: $unit->is_main,
                    address: $profiles->get($unit->tenant_id)?->address,
                    avatar_url: $profiles->get($unit->tenant_id)?->avatar_url,
                ), $brand->units),
            );
        });
    }
}

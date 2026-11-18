<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicPage\Tests\Concerns;

use Domain\Identity\Models\User;
use Domain\Settings\Actions\CreateDefaultSettings;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Models\ProfileSettings;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;

/** A brand with branches, built straight in the database (needs ScheduleFixtures for the main unit). */
trait BrandFixtures
{
    /** A branch of the owner's brand, with its own settings and address; the brand becomes multi-unit. */
    protected function branch(User $owner, string $slug, string $street = 'Avenida Paulista', bool $active = true): Tenant
    {
        $organization = Organization::query()->findOrFail($owner->organization_id);
        $organization->update(['type' => OrganizationType::MultiUnit]);
        $branch = Tenant::factory()->branchOf($organization)->create(['slug' => $slug, 'name' => ucfirst($slug), 'active' => $active]);
        app(CurrentTenant::class)->run($branch->id, function () use ($slug, $street): void {
            app(CreateDefaultSettings::class)->handle(new DefaultSettingsData(name: ucfirst($slug)));
            ProfileSettings::query()->firstOrFail()->update([...self::SHOP_ADDRESS, 'address_street' => $street]);
        });

        return $branch;
    }
}

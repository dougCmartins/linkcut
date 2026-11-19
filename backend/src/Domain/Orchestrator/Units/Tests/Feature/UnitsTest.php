<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Units\Tests\Feature;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Identity\Tests\Concerns\SignsIn;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Settings\Enums\ThemeMode;
use Domain\Settings\Models\Image;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\Service;
use Domain\Settings\Models\ThemeSettings;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Main unit and branches of one brand (docs/adr/0002, docs/decisoes/0001 P1). */
final class UnitsTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;
    use SignsIn;

    private const BRANCH_ADDRESS = [
        'zip_code' => '01310100',
        'street' => 'Avenida Paulista',
        'number' => '900',
        'district' => 'Bela Vista',
        'city' => 'São Paulo',
        'state' => 'SP',
        'complement' => null,
        'latitude' => -23.5651,
        'longitude' => -46.6519,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparePassport();
        Storage::fake(Image::DISK);
        Http::preventStrayRequests();
    }

    private function createBranch(User $owner, string $slug = 'rafael-paulista', bool $copy = false): TestResponse
    {
        $this->actingAsOwner($owner);

        return $this->postJson('/api/organization/units', [
            'name' => 'Rafael Paulista',
            'slug' => $slug,
            'address' => self::BRANCH_ADDRESS,
            'copy_settings' => $copy,
        ]);
    }

    private function profileNameOf(int $tenantId): string
    {
        return app(CurrentTenant::class)->run($tenantId, fn (): string => ProfileSettings::query()->value('name'));
    }

    public function test_a_barber_on_their_own_is_an_individual_organization_with_one_unit(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $this->actingAsOwner($owner);

        // Act
        $response = $this->getJson('/api/organization/units');

        // Assert
        $response->assertOk()
            ->assertJsonPath('organization_type', 'individual')
            ->assertJsonPath('can_manage', true)
            ->assertJsonCount(1, 'units')
            ->assertJsonPath('units.0.slug', 'rafael')
            ->assertJsonPath('units.0.is_main', true);
    }

    public function test_the_owner_creates_a_branch_with_its_own_link_and_address_and_the_brand_becomes_multi_unit(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');

        // Act
        $response = $this->createBranch($owner);

        // Assert
        $response->assertCreated()
            ->assertJsonPath('slug', 'rafael-paulista')
            ->assertJsonPath('is_main', false)
            ->assertJsonPath('onboarded', false);
        $branch = Tenant::query()->where('slug', 'rafael-paulista')->firstOrFail();
        $this->assertSame($owner->organization_id, $branch->organization_id);
        $this->assertSame(OrganizationType::MultiUnit, Organization::query()->findOrFail($owner->organization_id)->type);
        $profile = app(CurrentTenant::class)->run($branch->id, fn () => ProfileSettings::query()->firstOrFail());
        $this->assertSame('Rafael Paulista', $profile->name);
        $this->assertSame('Avenida Paulista', $profile->address_street);
        $this->getJson('/api/organization/units')->assertJsonPath('organization_type', 'multi_unit')
            ->assertJsonCount(2, 'units')->assertJsonPath('units.0.is_main', true)->assertJsonPath('units.1.slug', 'rafael-paulista');
    }

    public function test_the_branch_can_start_with_a_one_time_copy_of_the_main_unit_settings_without_its_images(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        app(CurrentTenant::class)->run($owner->tenant_id, function (): void {
            Service::query()->create(['name' => 'Pigmentação', 'duration_minutes' => 30, 'price_in_cents' => 4000, 'cost_in_cents' => 500, 'position' => 9]);
            ThemeSettings::query()->firstOrFail()->update(['mode' => ThemeMode::Light]);
            $image = Image::query()->create(['path' => 'x.webp', 'mime' => 'image/webp', 'size' => 1, 'width' => 1, 'height' => 1]);
            ProfileSettings::query()->firstOrFail()->update(['avatar_image_id' => $image->id, 'bio' => 'Cortes clássicos']);
        });

        // Act
        $response = $this->createBranch($owner, copy: true);

        // Assert
        $response->assertCreated();
        $branchId = (int) $response->json('id');
        [$services, $theme, $profile] = app(CurrentTenant::class)->run($branchId, fn (): array => [
            Service::query()->pluck('name')->all(),
            ThemeSettings::query()->firstOrFail(),
            ProfileSettings::query()->firstOrFail(),
        ]);
        $this->assertContains('Pigmentação', $services);
        $this->assertSame(ThemeMode::Light, $theme->mode);
        $this->assertSame('Cortes clássicos', $profile->bio);
        $this->assertNull($profile->avatar_image_id);
        $this->assertSame('Rafael Paulista', $profile->name);
    }

    public function test_the_unit_picked_at_the_top_decides_what_the_dashboard_reads_and_the_units_do_not_mix(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $branchId = (int) $this->createBranch($owner)->json('id');

        // Act
        $main = $this->getJson('/api/settings');
        $branch = $this->getJson('/api/settings', ['X-Unit' => (string) $branchId]);
        $tenant = $this->getJson('/api/tenant', ['X-Unit' => (string) $branchId]);

        // Assert
        $this->assertSame($this->profileNameOf($owner->tenant_id), $main->json('profile.name'));
        $this->assertSame('Rafael Paulista', $branch->json('profile.name'));
        $this->assertNotSame($main->json('profile.name'), $branch->json('profile.name'));
        $tenant->assertOk()->assertJsonPath('slug', 'rafael-paulista')->assertJsonPath('is_main', false);
    }

    public function test_a_unit_of_another_organization_is_refused_with_403(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $other = $this->barbershop('outra');
        $this->actingAsOwner($owner);

        // Act
        $foreign = $this->getJson('/api/settings', ['X-Unit' => (string) $other->tenant_id]);
        $garbage = $this->getJson('/api/settings', ['X-Unit' => '1 OR 1=1']);

        // Assert
        $foreign->assertForbidden()->assertJsonPath('code', 'UNIT_FORBIDDEN');
        $garbage->assertForbidden()->assertJsonPath('code', 'UNIT_FORBIDDEN');
    }

    public function test_only_the_owner_opens_other_units_or_creates_branches(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $branchId = (int) $this->createBranch($owner)->json('id');
        $barber = User::factory()->create(['tenant_id' => $owner->tenant_id, 'role' => UserRole::Barber]);
        $this->actingAsOwner($barber);

        // Act
        $opens = $this->getJson('/api/settings', ['X-Unit' => (string) $branchId]);
        $lists = $this->getJson('/api/organization/units');
        $creates = $this->postJson('/api/organization/units', ['name' => 'Outra', 'slug' => 'rafael-outra', 'address' => self::BRANCH_ADDRESS]);

        // Assert
        $opens->assertForbidden()->assertJsonPath('code', 'UNIT_FORBIDDEN');
        $lists->assertOk()->assertJsonPath('can_manage', false)->assertJsonCount(1, 'units')->assertJsonPath('units.0.id', $owner->tenant_id);
        $creates->assertForbidden()->assertJsonPath('code', 'OWNER_ONLY');
        $this->assertFalse(Tenant::query()->where('slug', 'rafael-outra')->exists());
    }

    public function test_a_branch_needs_a_free_link(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $this->barbershop('ocupado');

        // Act
        $taken = $this->createBranch($owner, slug: 'ocupado');
        $reserved = $this->createBranch($owner, slug: 'painel');

        // Assert
        $taken->assertStatus(409)->assertJsonPath('code', 'SLUG_UNAVAILABLE');
        $reserved->assertStatus(409)->assertJsonPath('code', 'SLUG_UNAVAILABLE');
        $this->assertSame(1, Tenant::query()->where('organization_id', $owner->organization_id)->count());
    }

    public function test_deleting_a_branch_keeps_the_main_unit_and_deleting_the_main_unit_deletes_the_whole_brand(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $first = (int) $this->createBranch($owner, slug: 'rafael-paulista')->json('id');
        $second = (int) $this->createBranch($owner, slug: 'rafael-centro')->json('id');

        // Act
        $deleteBranch = $this->deleteJson('/api/account', ['password' => 'password', 'slug_confirmation' => 'rafael-paulista'], ['X-Unit' => (string) $first]);

        // Assert
        $deleteBranch->assertNoContent();
        $this->assertFalse(Tenant::query()->whereKey($first)->exists());
        $this->assertTrue(Tenant::query()->whereKey($owner->tenant_id)->exists());

        // Act
        $deleteBrand = $this->deleteJson('/api/account', ['password' => 'password', 'slug_confirmation' => 'rafael']);

        // Assert
        $deleteBrand->assertNoContent();
        $this->assertFalse(Tenant::query()->whereKey([$owner->tenant_id, $second])->exists());
        $this->assertFalse(Organization::query()->whereKey($owner->organization_id)->exists());
        $this->assertFalse(User::query()->whereKey($owner->id)->exists());
    }
}

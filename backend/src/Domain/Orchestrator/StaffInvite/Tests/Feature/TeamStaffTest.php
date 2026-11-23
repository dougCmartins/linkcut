<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffInvite\Tests\Feature;

use Domain\Finance\Models\RevenueEntry;
use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Settings\Actions\CreateDefaultSettings;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffInvite;
use Domain\Team\Models\StaffPermission;
use Domain\Team\Support\StaffInviteToken;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class TeamStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_staff_and_list_includes_pending_member(): void
    {
        // Arrange
        Notification::fake();
        [$owner] = $this->ownerWithUnit();
        Passport::actingAs($owner);

        // Act
        $created = $this->postJson('/api/team/staff', ['name' => 'João', 'email' => 'joao@example.com']);
        $list = $this->getJson('/api/team/staff');

        // Assert
        $created->assertCreated();
        $created->assertJsonPath('name', 'João');
        $created->assertJsonPath('pending_invite', true);
        $created->assertJsonPath('permissions.see_prices', false);
        $list->assertOk();
        $this->assertGreaterThanOrEqual(2, count($list->json()));
        $this->assertTrue(StaffInvite::query()->where('email', 'joao@example.com')->exists());
    }

    public function test_barber_without_see_prices_gets_null_money_on_finance_api(): void
    {
        // Arrange
        [$owner, $tenant] = $this->ownerWithUnit();
        $barber = $this->barberOnUnit($owner, $tenant, seePrices: false);
        Passport::actingAs($barber);
        app(CurrentTenant::class)->run($tenant->id, function (): void {
            // seed a revenue row via DB for month-to-date
            RevenueEntry::query()->create([
                'date' => now()->toDateString(),
                'time' => '10:00',
                'client_name' => 'Cliente',
                'service_name' => 'Corte',
                'price_in_cents' => 5000,
                'cost_in_cents' => 500,
            ]);
        });

        // Act
        $response = $this->getJson('/api/finance/month-to-date');

        // Assert
        $response->assertOk();
        $json = $response->json();
        $this->assertTrue(
            $this->everyMoneyFieldIsNull($json),
            'Expected money fields to be null for a barber without see_prices: '.json_encode($json),
        );
    }

    public function test_barber_cannot_read_another_staff_member(): void
    {
        // Arrange
        [$owner, $tenant] = $this->ownerWithUnit();
        $barber = $this->barberOnUnit($owner, $tenant);
        $other = app(CurrentTenant::class)->run($tenant->id, function () use ($owner): Staff {
            $staff = Staff::query()->create([
                'user_id' => null,
                'name' => 'Outro',
                'active' => true,
            ]);
            StaffPermission::query()->create(['staff_id' => $staff->id]);
            StaffInvite::query()->create([
                'staff_id' => $staff->id,
                'email' => 'outro@example.com',
                'token_hash' => StaffInviteToken::hash('x'),
                'expires_at' => now()->addDay(),
                'created_by' => $owner->id,
            ]);

            return $staff;
        });
        Passport::actingAs($barber);

        // Act
        $list = $this->getJson('/api/team/staff');

        // Assert
        $list->assertOk();
        $ids = collect($list->json())->pluck('id')->all();
        $this->assertNotContains($other->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_invite_expires_and_cannot_be_reused(): void
    {
        // Arrange
        $this->preparePassport();
        Notification::fake();
        [$owner, $tenant] = $this->ownerWithUnit();
        Passport::actingAs($owner);
        $this->postJson('/api/team/staff', ['name' => 'Ana', 'email' => 'ana@example.com'])->assertCreated();
        $invite = StaffInvite::query()->where('email', 'ana@example.com')->firstOrFail();
        $token = str_repeat('a', 64);
        $invite->update(['token_hash' => StaffInviteToken::hash($token), 'expires_at' => now()->subMinute()]);

        // Act
        $expired = $this->postJson('/api/public/staff-invites/'.$token, [
            'name' => 'Ana',
            'password' => 'password1',
        ]);

        // Assert
        $expired->assertStatus(410)->assertJsonPath('code', 'STAFF_INVITE_INVALID');

        // Arrange — valid token, then reuse
        $token2 = str_repeat('b', 64);
        $invite->update([
            'token_hash' => StaffInviteToken::hash($token2),
            'expires_at' => now()->addDay(),
            'accepted_at' => null,
        ]);
        Staff::withoutTenantScope()->whereKey($invite->staff_id)->update(['user_id' => null]);

        $first = $this->postJson('/api/public/staff-invites/'.$token2, [
            'name' => 'Ana',
            'password' => 'password1',
        ]);
        $second = $this->postJson('/api/public/staff-invites/'.$token2, [
            'name' => 'Ana',
            'password' => 'password1',
        ]);

        // Assert
        $first->assertCreated();
        $second->assertStatus(410)->assertJsonPath('code', 'STAFF_INVITE_INVALID');
        $this->assertSame(UserRole::Barber, User::query()->where('email', 'ana@example.com')->firstOrFail()->role);
    }

    public function test_barber_cannot_switch_to_another_unit(): void
    {
        // Arrange
        [$owner, $main] = $this->ownerWithUnit();
        Passport::actingAs($owner);
        $branchId = (int) $this->postJson('/api/organization/units', [
            'name' => 'Filial',
            'slug' => 'filial-team',
            'copy_settings' => false,
            'address' => [
                'zip_code' => '01304001',
                'street' => 'Av Paulista',
                'number' => '1000',
                'complement' => null,
                'district' => 'Bela Vista',
                'city' => 'São Paulo',
                'state' => 'SP',
                'latitude' => -23.5578,
                'longitude' => -46.6609,
            ],
        ])->json('id');
        $barber = $this->barberOnUnit($owner, $main);
        Passport::actingAs($barber);

        // Act
        $response = $this->getJson('/api/team/staff', ['X-Unit' => (string) $branchId]);

        // Assert
        $response->assertForbidden();
    }

    /** @return array{0: User, 1: Tenant} */
    private function ownerWithUnit(): array
    {
        $organization = Organization::factory()->create(['slug' => 'marca-team', 'name' => 'Marca Team']);
        $main = Tenant::factory()->create([
            'organization_id' => $organization->id,
            'is_main' => true,
            'slug' => 'marca-team',
            'name' => 'Marca Team',
        ]);
        $user = User::factory()->create([
            'tenant_id' => $main->id,
            'organization_id' => $organization->id,
            'role' => UserRole::Owner,
            'name' => 'Owner',
        ]);
        $organization->update(['owner_user_id' => $user->id]);
        app(CurrentTenant::class)->run($main->id, function () use ($user): void {
            app(CreateDefaultSettings::class)->handle(new DefaultSettingsData(name: 'Marca Team'));
            $staff = Staff::query()->create([
                'user_id' => $user->id,
                'name' => $user->name,
                'active' => true,
            ]);
            StaffPermission::query()->create(['staff_id' => $staff->id]);
        });

        return [$user, $main];
    }

    private function barberOnUnit(User $owner, Tenant $tenant, bool $seePrices = false): User
    {
        $barber = User::factory()->create([
            'tenant_id' => $tenant->id,
            'organization_id' => $owner->organization_id,
            'role' => UserRole::Barber,
            'name' => 'Barber',
            'email' => 'barber'.uniqid().'@example.com',
        ]);
        app(CurrentTenant::class)->run($tenant->id, function () use ($barber, $seePrices): void {
            $staff = Staff::query()->create([
                'user_id' => $barber->id,
                'name' => $barber->name,
                'active' => true,
            ]);
            StaffPermission::query()->create([
                'staff_id' => $staff->id,
                'see_prices' => $seePrices,
                'see_own_commission' => false,
                'see_unit_agenda' => false,
            ]);
        });

        return $barber;
    }

    /** @param  array<mixed>  $data */
    private function everyMoneyFieldIsNull(array $data): bool
    {
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $lower = strtolower($key);
                $isMoney = str_ends_with($lower, '_in_cents')
                    || str_contains($lower, 'price')
                    || str_contains($lower, 'revenue')
                    || str_contains($lower, 'cost')
                    || str_contains($lower, 'total')
                    || str_contains($lower, 'amount');
                if ($isMoney && $value !== null) {
                    return false;
                }
            }
            if (is_array($value) && ! $this->everyMoneyFieldIsNull($value)) {
                return false;
            }
        }

        return true;
    }
}

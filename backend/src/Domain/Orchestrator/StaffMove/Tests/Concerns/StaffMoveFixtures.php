<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Tests\Concerns;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Settings\Models\Service;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffPermission;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Testing\TestResponse;

/** A brand with a branch and a barber of the team, and the move request (needs ScheduleFixtures). */
trait StaffMoveFixtures
{
    /** The owner's brand gets a branch through the real "Nova filial" flow; returns its id. */
    protected function branchOf(User $owner, string $slug = 'rafael-centro'): int
    {
        $this->actingAsOwner($owner);

        return (int) $this->postJson('/api/organization/units', [
            'name' => 'Rafael Centro',
            'slug' => $slug,
            'copy_settings' => false,
            'address' => [
                'zip_code' => '01304001', 'street' => 'Rua Augusta', 'number' => '100', 'complement' => null,
                'district' => 'Consolação', 'city' => 'São Paulo', 'state' => 'SP', 'latitude' => -23.5578, 'longitude' => -46.6609,
            ],
        ])->assertCreated()->json('id');
    }

    /** A barber of the team at the owner's main unit, who sees their own commission. */
    protected function teamBarber(User $owner): array
    {
        $barber = User::factory()->create(['tenant_id' => $owner->tenant_id, 'role' => UserRole::Barber]);
        $staff = app(CurrentTenant::class)->run($owner->tenant_id, function () use ($barber): Staff {
            $staff = Staff::query()->create(['user_id' => $barber->id, 'name' => 'João Barbeiro', 'active' => true]);
            StaffPermission::query()->create(['staff_id' => $staff->id, 'see_own_commission' => true]);

            return $staff;
        });

        return [$barber, $staff];
    }

    /** @return list<int> */
    protected function servicesOf(int $unitId): array
    {
        return app(CurrentTenant::class)->run($unitId, fn (): array => Service::query()->pluck('id')->map(fn ($id): int => (int) $id)->all());
    }

    protected function move(int $staffId, int $toUnitId, array $overrides = [], ?int $fromUnitId = null): TestResponse
    {
        $headers = $fromUnitId ? ['X-Unit' => (string) $fromUnitId] : [];

        return $this->postJson("/api/team/staff/{$staffId}/move", [
            'to_unit_id' => $toUnitId,
            'kind' => 'transfer',
            'weekly_hours' => array_map(fn (int $weekday): array => [
                'weekday' => $weekday, 'is_open' => $weekday !== 0, 'opens_at' => '09:00', 'closes_at' => '18:00',
                'break_starts_at' => '12:00', 'break_ends_at' => '13:00',
            ], range(0, 6)),
            'service_ids' => $this->servicesOf($toUnitId),
            'permissions' => 'keep',
            ...$overrides,
        ], $headers);
    }
}

<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Tests\Feature;

use Domain\Orchestrator\StaffMove\Tests\Concerns\StaffMoveFixtures;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** The reads behind the move screen (docs/decisoes/0003 P3). */
final class MoveOptionsTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;
    use StaffMoveFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning('10:00');
        Notification::fake();
    }

    public function test_the_form_starts_with_the_destination_hours_and_services_without_prices(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $branchId = $this->branchOf($owner);
        [, $staff] = $this->teamBarber($owner);

        // Act
        $response = $this->getJson("/api/team/staff/{$staff->id}/move-options?to_unit_id={$branchId}");

        // Assert
        $response->assertOk()->assertJsonCount(7, 'weekly_hours')->assertJsonPath('weekly_hours.0.break_starts_at', null);
        $this->assertSame($this->servicesOf($branchId), array_column($response->json('services'), 'id'));
        $this->assertStringNotContainsString('price', (string) $response->getContent());
    }

    public function test_the_owner_lists_the_current_moves_with_the_unit_names_and_a_barber_cannot(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $branchId = $this->branchOf($owner);
        [$barber, $staff] = $this->teamBarber($owner);
        $this->move($staff->id, $branchId, ['kind' => 'loan', 'ends_on' => '2026-10-23'])->assertCreated();

        // Act
        $list = $this->getJson('/api/team/moves');
        $sameUnit = $this->getJson("/api/team/staff/{$staff->id}/move-options?to_unit_id={$owner->tenant_id}");
        $this->app['auth']->forgetGuards();
        Passport::actingAs($barber->fresh());
        $byBarber = $this->getJson('/api/team/moves');

        // Assert
        $list->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.kind', 'loan')->assertJsonPath('0.ends_on', '2026-10-23')
            ->assertJsonPath('0.barber_name', 'João Barbeiro')->assertJsonPath('0.to_unit_name', 'Rafael Centro')
            ->assertJsonPath('0.from_unit_id', $owner->tenant_id);
        $sameUnit->assertUnprocessable()->assertJsonPath('code', 'MOVE_DESTINATION_INVALID');
        $byBarber->assertForbidden();
    }
}

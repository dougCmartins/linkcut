<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Tests\Feature;

use Carbon\CarbonImmutable;
use Domain\Identity\Models\User;
use Domain\Orchestrator\StaffMove\Tests\Concerns\StaffMoveFixtures;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Models\BookingRequest;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Settings\Models\Service;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffMove;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** A barber lent to another unit until a date (docs/decisoes/0003 P2). Today is Monday 19/10/2026. */
final class LoanTest extends TestCase
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

    /** A loan from the main unit to the branch until Friday 23/10; returns [owner, branch id, barber, origin row, move id]. */
    private function lend(string $endsOn = '2026-10-23'): array
    {
        $owner = $this->barbershop('rafael');
        $branchId = $this->branchOf($owner);
        [$barber, $staff] = $this->teamBarber($owner);
        $moveId = (int) $this->move($staff->id, $branchId, ['kind' => 'loan', 'ends_on' => $endsOn])->assertCreated()->json('id');

        return [$owner, $branchId, $barber, $staff, $moveId];
    }

    private function rowAt(int $unitId, User $barber): Staff
    {
        return app(CurrentTenant::class)->run($unitId, fn () => Staff::query()->where('user_id', $barber->id)->sole());
    }

    public function test_a_loan_needs_an_end_after_today(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $branchId = $this->branchOf($owner);
        [, $staff] = $this->teamBarber($owner);

        // Act
        $without = $this->move($staff->id, $branchId, ['kind' => 'loan']);
        $today = $this->move($staff->id, $branchId, ['kind' => 'loan', 'ends_on' => '2026-10-19']);
        $later = $this->move($staff->id, $branchId, ['kind' => 'loan', 'ends_on' => '2026-10-23']);

        // Assert
        $without->assertUnprocessable()->assertJsonPath('code', 'LOAN_END_INVALID');
        $today->assertUnprocessable()->assertJsonPath('code', 'LOAN_END_INVALID');
        $later->assertCreated()->assertJsonPath('kind', 'loan')->assertJsonPath('ends_on', '2026-10-23')->assertJsonPath('status', 'active');
    }

    public function test_the_owner_extends_ends_now_or_makes_the_loan_permanent_and_a_barber_cannot(): void
    {
        // Arrange
        [$owner, $branchId, $barber, $staff, $moveId] = $this->lend();

        // Act
        $earlier = $this->patchJson("/api/team/moves/{$moveId}/extend", ['ends_on' => '2026-10-22']);
        $extended = $this->patchJson("/api/team/moves/{$moveId}/extend", ['ends_on' => '2026-10-30']);
        $this->app['auth']->forgetGuards();
        Passport::actingAs($barber->fresh());
        $byBarber = $this->postJson("/api/team/moves/{$moveId}/end");
        $this->actingAsOwner($owner);
        $ended = $this->postJson("/api/team/moves/{$moveId}/end");
        $again = $this->postJson("/api/team/moves/{$moveId}/end");

        // Assert
        $earlier->assertUnprocessable()->assertJsonPath('code', 'LOAN_END_INVALID');
        $extended->assertOk()->assertJsonPath('ends_on', '2026-10-30');
        $byBarber->assertForbidden();
        $ended->assertSuccessful()->assertJsonPath('status', 'ended');
        $again->assertNotFound()->assertJsonPath('code', 'STAFF_MOVE_NOT_FOUND');
        $this->assertSame($owner->tenant_id, $barber->fresh()->tenant_id);
        $this->assertTrue($this->rowAt($owner->tenant_id, $barber)->active);
        $this->assertFalse($this->rowAt($branchId, $barber)->active);
        $this->assertSame($staff->id, $this->rowAt($owner->tenant_id, $barber)->id);
    }

    public function test_making_the_loan_permanent_keeps_the_barber_at_the_destination_for_good(): void
    {
        // Arrange
        [, $branchId, $barber, , $moveId] = $this->lend();

        // Act
        $permanent = $this->postJson("/api/team/moves/{$moveId}/make-permanent");
        $this->travelTo(CarbonImmutable::parse('2026-10-24 01:00', 'America/Sao_Paulo'));
        $this->artisan('team:end-loans')->expectsOutput('Loans ended: 0')->assertSuccessful();

        // Assert
        $permanent->assertSuccessful()->assertJsonPath('status', 'made_permanent')->assertJsonPath('ends_on', null);
        $this->assertSame($branchId, $barber->fresh()->tenant_id);
        $this->assertTrue($this->rowAt($branchId, $barber)->active);
    }

    public function test_bookings_still_to_attend_at_the_destination_refuse_ending_the_loan(): void
    {
        // Arrange
        [, $branchId, $barber, , $moveId] = $this->lend();
        $toStaff = $this->rowAt($branchId, $barber);
        $cut = app(CurrentTenant::class)->run($branchId, fn () => Service::query()->where('name', 'Corte')->firstOrFail());
        app(CurrentTenant::class)->run($branchId, fn () => BookingRequest::query()->create([
            'staff_id' => $toStaff->id, 'client_name' => 'Ana Souza', 'client_whatsapp' => '11912345678', 'service_id' => $cut->id,
            'service_name' => 'Corte', 'duration_minutes' => 45, 'date' => '2026-10-22', 'starts_at' => '15:00', 'ends_at' => '15:45',
            'status' => BookingStatus::Confirmed,
        ]));

        // Act
        $response = $this->postJson("/api/team/moves/{$moveId}/end");

        // Assert
        $response->assertStatus(409)->assertJsonPath('code', 'STAFF_HAS_UPCOMING_APPOINTMENTS')->assertJsonPath('data.appointments.0.date', '2026-10-22');
        $this->assertSame($branchId, $barber->fresh()->tenant_id);
        $this->assertSame('active', StaffMove::query()->findOrFail($moveId)->status->value);
    }

    public function test_the_daily_command_brings_the_barber_back_the_day_after_the_loan_and_running_it_again_changes_nothing(): void
    {
        // Arrange
        [$owner, $branchId, $barber] = $this->lend('2026-10-23');

        // Act: still the last day, then the day after (twice)
        $this->travelTo(CarbonImmutable::parse('2026-10-23 23:00', 'America/Sao_Paulo'));
        $this->artisan('team:end-loans')->expectsOutput('Loans ended: 0')->assertSuccessful();
        $this->travelTo(CarbonImmutable::parse('2026-10-24 00:10', 'America/Sao_Paulo'));
        $this->artisan('team:end-loans')->expectsOutput('Loans ended: 1')->assertSuccessful();
        $this->artisan('team:end-loans')->expectsOutput('Loans ended: 0')->assertSuccessful();

        // Assert
        $this->assertSame($owner->tenant_id, $barber->fresh()->tenant_id);
        $this->assertTrue($this->rowAt($owner->tenant_id, $barber)->active);
        $this->assertFalse($this->rowAt($branchId, $barber)->active);
    }

    public function test_the_public_agenda_of_the_destination_offers_the_barber_only_until_the_end_of_the_loan(): void
    {
        // Arrange
        [, $branchId, $barber] = $this->lend('2026-10-21');
        $toStaff = $this->rowAt($branchId, $barber);
        $cutId = (int) app(CurrentTenant::class)->run($branchId, fn () => Service::query()->where('name', 'Corte')->value('id'));
        $this->app['auth']->forgetGuards();

        // Act
        $availability = $this->getJson("/api/public/rafael-centro/availability?service_ids[]={$cutId}&staff_id={$toStaff->id}");
        $after = $this->postJson('/api/public/rafael-centro/booking-requests', [
            'client_name' => 'João Silva', 'client_whatsapp' => '(11) 91234-5678', 'client_email' => 'joao@example.com',
            'service_ids' => [$cutId], 'staff_id' => $toStaff->id, 'date' => '2026-10-22', 'time' => '10:00',
        ]);

        // Assert
        $availability->assertOk();
        $days = collect($availability->json('days'))->keyBy('date');
        $this->assertNotEmpty($days['2026-10-21']['times']);
        $this->assertSame([], $days['2026-10-22']['times']);
        $after->assertNotFound()->assertJsonPath('code', 'STAFF_NOT_FOUND');
    }
}

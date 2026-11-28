<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Tests\Feature;

use Carbon\CarbonImmutable;
use Domain\Finance\Models\CommissionEntry;
use Domain\Orchestrator\StaffMove\Notifications\StaffMovedNotification;
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

/** A barber transferred to another unit of the brand (docs/decisoes/0003 P1). */
final class MoveStaffTest extends TestCase
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

    public function test_the_barber_moves_to_the_branch_with_new_hours_and_their_login_opens_it(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $branchId = $this->branchOf($owner);
        [$barber, $staff] = $this->teamBarber($owner);

        // Act
        $response = $this->move($staff->id, $branchId);

        // Assert
        $response->assertCreated()->assertJsonPath('kind', 'transfer')->assertJsonPath('status', 'active')->assertJsonPath('ends_on', null);
        $this->assertSame($branchId, $barber->fresh()->tenant_id);
        $this->assertFalse(app(CurrentTenant::class)->run($owner->tenant_id, fn () => Staff::query()->findOrFail($staff->id)->active));
        $destination = app(CurrentTenant::class)->run($branchId, fn () => Staff::query()->where('user_id', $barber->id)->with('permissions')->sole());
        $this->assertTrue($destination->active);
        $this->assertTrue($destination->permissions->see_own_commission);
        $this->assertSame(1, StaffMove::query()->where('user_id', $barber->id)->count());
        Notification::assertSentTo($barber, StaffMovedNotification::class);

        // Act: the barber signs in at the branch; the old unit is closed to them
        $this->app['auth']->forgetGuards();
        Passport::actingAs($barber->fresh());
        $tenant = $this->getJson('/api/tenant');
        $old = $this->getJson('/api/tenant', ['X-Unit' => (string) $owner->tenant_id]);

        // Assert
        $tenant->assertOk()->assertJsonPath('slug', 'rafael-centro');
        $old->assertForbidden()->assertJsonPath('code', 'UNIT_FORBIDDEN');
    }

    public function test_past_earnings_stay_at_the_origin_and_new_ones_follow_the_destination_rules(): void
    {
        // Arrange: 50% at the main unit, one appointment done there
        $owner = $this->barbershop('rafael');
        $branchId = $this->branchOf($owner);
        [, $staff] = $this->teamBarber($owner);
        $this->actingAsOwner($owner);
        $this->postJson('/api/finance/commission/rules', ['service_id' => $this->serviceId($owner, 'Corte'), 'staff_id' => null, 'percent' => 500, 'starts_on' => '2026-01-01'])->assertSuccessful();
        $done = $this->bookingRequest($owner, '2026-10-19', '09:00', BookingStatus::Confirmed, attributes: ['staff_id' => $staff->id]);
        $this->postJson("/api/schedule/requests/{$done->id}/complete")->assertSuccessful();
        $before = app(CurrentTenant::class)->run($owner->tenant_id, fn () => CommissionEntry::query()->sole()->only(['tenant_id', 'amount_in_cents']));

        // Act: transfer, then 30% at the branch and one appointment done there
        $this->move($staff->id, $branchId)->assertCreated();
        $branchCut = app(CurrentTenant::class)->run($branchId, fn () => Service::query()->where('name', 'Corte')->firstOrFail());
        $this->postJson('/api/finance/commission/rules', ['service_id' => $branchCut->id, 'staff_id' => null, 'percent' => 300, 'starts_on' => '2026-01-01'], ['X-Unit' => (string) $branchId])->assertSuccessful();
        $toStaffId = (int) app(CurrentTenant::class)->run($branchId, fn () => Staff::query()->where('name', 'João Barbeiro')->value('id'));
        $there = app(CurrentTenant::class)->run($branchId, fn () => BookingRequest::query()->create([
            'staff_id' => $toStaffId, 'client_name' => 'Ana Souza', 'client_whatsapp' => '11912345678', 'service_id' => $branchCut->id,
            'service_name' => 'Corte', 'duration_minutes' => 45, 'date' => '2026-10-19', 'starts_at' => '15:00', 'ends_at' => '15:45',
            'status' => BookingStatus::Confirmed,
        ]));
        $this->travelTo(CarbonImmutable::parse('2026-10-19 18:00', 'America/Sao_Paulo'));
        $this->postJson("/api/schedule/requests/{$there->id}/complete", [], ['X-Unit' => (string) $branchId])->assertSuccessful();

        // Assert
        $after = app(CurrentTenant::class)->run($owner->tenant_id, fn () => CommissionEntry::query()->sole()->only(['tenant_id', 'amount_in_cents']));
        $this->assertSame($before, $after);
        $atBranch = app(CurrentTenant::class)->run($branchId, fn () => CommissionEntry::query()->sole());
        $this->assertSame((int) round($branchCut->price_in_cents * 0.3), (int) $atBranch->amount_in_cents);
    }

    public function test_upcoming_appointments_at_the_origin_refuse_the_move_and_nothing_changes(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $branchId = $this->branchOf($owner);
        [$barber, $staff] = $this->teamBarber($owner);
        $booking = $this->bookingRequest($owner, '2026-10-21', '14:00', BookingStatus::Confirmed, attributes: ['staff_id' => $staff->id]);

        // Act
        $response = $this->move($staff->id, $branchId);

        // Assert
        $response->assertStatus(409)
            ->assertJsonPath('code', 'STAFF_HAS_UPCOMING_APPOINTMENTS')
            ->assertJsonPath('data.appointments.0.id', $booking->id)
            ->assertJsonPath('data.appointments.0.date', '2026-10-21')
            ->assertJsonPath('data.appointments.0.client_name', 'Ana Souza');
        $this->assertSame($owner->tenant_id, $barber->fresh()->tenant_id);
        $this->assertTrue(app(CurrentTenant::class)->run($owner->tenant_id, fn () => Staff::query()->findOrFail($staff->id)->active));
        $this->assertSame(0, StaffMove::query()->count());
        $this->assertFalse(app(CurrentTenant::class)->run($branchId, fn () => Staff::query()->where('user_id', $barber->id)->exists()));
    }

    public function test_only_the_owner_moves_a_barber_with_a_login_to_another_unit_of_the_brand(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $branchId = $this->branchOf($owner);
        $otherBrand = $this->barbershop('outra');
        [$barber, $staff] = $this->teamBarber($owner);
        $invited = app(CurrentTenant::class)->run($owner->tenant_id, fn () => Staff::query()->create(['user_id' => null, 'name' => 'Convidado', 'active' => true]));
        $this->actingAsOwner($owner);

        // Act
        $foreign = $this->move($staff->id, $otherBrand->tenant_id);
        $same = $this->move($staff->id, $owner->tenant_id);
        $pending = $this->move($invited->id, $branchId);
        $originServices = $this->move($staff->id, $branchId, ['service_ids' => $this->servicesOf($owner->tenant_id)]);
        $loan = $this->move($staff->id, $branchId, ['kind' => 'loan']);
        $this->app['auth']->forgetGuards();
        Passport::actingAs($barber);
        $byBarber = $this->move($staff->id, $branchId);

        // Assert
        $foreign->assertUnprocessable()->assertJsonPath('code', 'MOVE_DESTINATION_INVALID');
        $same->assertUnprocessable()->assertJsonPath('code', 'MOVE_DESTINATION_INVALID');
        $pending->assertUnprocessable()->assertJsonPath('code', 'STAFF_NOT_MOVABLE');
        $originServices->assertStatus(409)->assertJsonPath('code', 'STAFF_SERVICE_UNAVAILABLE');
        $loan->assertUnprocessable();
        $byBarber->assertForbidden();
        $this->assertSame(0, StaffMove::query()->count());
        $this->assertSame($owner->tenant_id, $barber->fresh()->tenant_id);
    }

    public function test_moving_back_turns_the_old_row_on_again_instead_of_creating_another(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $branchId = $this->branchOf($owner);
        [$barber, $staff] = $this->teamBarber($owner);
        $this->move($staff->id, $branchId)->assertCreated();
        $atBranch = (int) app(CurrentTenant::class)->run($branchId, fn () => Staff::query()->where('user_id', $barber->id)->value('id'));

        // Act
        $back = $this->move($atBranch, $owner->tenant_id, ['permissions' => 'reset'], fromUnitId: $branchId);

        // Assert
        $back->assertCreated()->assertJsonPath('to_staff_id', $staff->id);
        $this->assertSame($owner->tenant_id, $barber->fresh()->tenant_id);
        $mainRows = app(CurrentTenant::class)->run($owner->tenant_id, fn () => Staff::query()->where('user_id', $barber->id)->with('permissions')->get());
        $this->assertCount(1, $mainRows);
        $this->assertTrue($mainRows->first()->active);
        $this->assertFalse($mainRows->first()->permissions->see_own_commission);
        $this->assertFalse(app(CurrentTenant::class)->run($branchId, fn () => Staff::query()->findOrFail($atBranch)->active));
        $this->assertSame(['ended', 'active'], StaffMove::query()->orderBy('id')->pluck('status')->map(fn ($status) => $status->value)->all());
    }
}

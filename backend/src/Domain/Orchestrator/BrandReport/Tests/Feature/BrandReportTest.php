<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BrandReport\Tests\Feature;

use Domain\Finance\Actions\ListCommissionReport;
use Domain\Finance\Actions\ListHistory;
use Domain\Finance\Data\CommissionReportQueryData;
use Domain\Finance\Data\HistoryQueryData;
use Domain\Finance\Models\CommissionEntry;
use Domain\Finance\Tests\Concerns\FinanceFixtures;
use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Orchestrator\BrandReport\Jobs\SendBrandReport;
use Domain\Orchestrator\BrandReport\Notifications\BrandReportNotification;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class BrandReportTest extends TestCase
{
    use FinanceFixtures;
    use RefreshDatabase;
    use ScheduleFixtures;

    private const PERIOD = 'from=2026-10-01&to=2026-10-31';

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning('18:00');
    }

    public function test_the_brand_total_equals_the_sum_of_the_units_read_one_by_one(): void
    {
        // Arrange
        [$owner, $branch] = $this->brandWithTwoUnits();
        $this->sale($owner, '2026-10-05', 5000, commission: 2500);
        $this->sale($owner, '2026-10-06', 4000, commission: 2000);
        $this->sale($branch, '2026-10-07', 7000, commission: 3500);
        $this->actingAsOwner($owner);

        // Act
        $response = $this->getJson('/api/finance/brand/report?'.self::PERIOD);

        // Assert
        $response->assertOk();
        $perUnit = collect([$owner, $branch])->map(fn (User $unitOwner): array => app(CurrentTenant::class)->run($unitOwner->tenant_id, fn (): array => [
            'revenue' => app(ListHistory::class)->handle(new HistoryQueryData(from: '2026-10-01', to: '2026-10-31'))->summary->total_in_cents,
            'count' => app(ListHistory::class)->handle(new HistoryQueryData(from: '2026-10-01', to: '2026-10-31'))->summary->count,
            'commission' => app(ListCommissionReport::class)->handle(new CommissionReportQueryData(from: '2026-10-01', to: '2026-10-31'))->total_amount_in_cents,
        ]));
        $response->assertJsonPath('total.revenue_in_cents', $perUnit->sum('revenue'));
        $response->assertJsonPath('total.count', $perUnit->sum('count'));
        $response->assertJsonPath('total.commission_in_cents', $perUnit->sum('commission'));
        $response->assertJsonPath('total.revenue_in_cents', 16000);
        $response->assertJsonPath('total.average_ticket_in_cents', 5333);
        $response->assertJsonPath('units.0.unit_id', $owner->tenant_id);
        $response->assertJsonPath('units.0.is_main', true);
        $response->assertJsonPath('units.0.totals.revenue_in_cents', 9000);
        $response->assertJsonPath('units.1.unit_id', $branch->tenant_id);
        $response->assertJsonPath('units.1.totals.commission_in_cents', 3500);
    }

    public function test_a_unit_without_sales_comes_with_zeros_and_other_periods_are_left_out(): void
    {
        // Arrange
        [$owner, $branch] = $this->brandWithTwoUnits();
        $this->sale($owner, '2026-10-05', 5000, commission: 2500);
        $this->sale($owner, '2026-09-30', 9900, commission: 100);
        $this->actingAsOwner($owner);

        // Act
        $response = $this->getJson('/api/finance/brand/report?'.self::PERIOD);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('total.revenue_in_cents', 5000);
        $response->assertJsonPath('units.1.unit_id', $branch->tenant_id);
        $response->assertJsonPath('units.1.totals', [
            'revenue_in_cents' => 0,
            'count' => 0,
            'average_ticket_in_cents' => 0,
            'commission_in_cents' => 0,
        ]);
    }

    public function test_another_organization_never_enters_the_sum(): void
    {
        // Arrange
        [$owner] = $this->brandWithTwoUnits();
        $this->sale($owner, '2026-10-05', 5000, commission: 2500);
        $stranger = $this->barbershop('stranger');
        $this->sale($stranger, '2026-10-05', 99900, commission: 50000);
        $this->actingAsOwner($owner);

        // Act
        $response = $this->getJson('/api/finance/brand/report?'.self::PERIOD);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('total.revenue_in_cents', 5000);
        $this->assertNotContains($stranger->tenant_id, collect($response->json('units'))->pluck('unit_id')->all());
    }

    public function test_a_barber_gets_403_in_the_error_envelope(): void
    {
        // Arrange
        [$owner] = $this->brandWithTwoUnits();
        $barber = User::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'organization_id' => $owner->organization_id,
            'role' => UserRole::Barber,
        ]);
        $this->app['auth']->forgetGuards();
        Passport::actingAs($barber);

        // Act
        $report = $this->getJson('/api/finance/brand/report?'.self::PERIOD);
        $csv = $this->postJson('/api/finance/brand/report', ['from' => '2026-10-01', 'to' => '2026-10-31']);

        // Assert
        $report->assertForbidden()->assertJsonPath('code', 'BRAND_REPORT_FORBIDDEN');
        $csv->assertForbidden()->assertJsonPath('code', 'BRAND_REPORT_FORBIDDEN');
    }

    public function test_the_period_is_validated_and_the_route_needs_a_session(): void
    {
        // Arrange
        [$owner] = $this->brandWithTwoUnits();

        // Act
        $anonymous = $this->getJson('/api/finance/brand/report?'.self::PERIOD);
        $this->actingAsOwner($owner);
        $invalid = $this->getJson('/api/finance/brand/report?from=2026-10-31&to=2026-10-01');

        // Assert
        $anonymous->assertUnauthorized();
        $invalid->assertStatus(422);
    }

    public function test_the_request_queues_the_csv_and_answers_at_once(): void
    {
        // Arrange
        Queue::fake();
        [$owner] = $this->brandWithTwoUnits();
        $this->actingAsOwner($owner);

        // Act
        $response = $this->postJson('/api/finance/brand/report', ['from' => '2026-10-01', 'to' => '2026-10-31']);

        // Assert
        $response->assertStatus(202);
        $this->assertNotSame($owner->email, $response->json('email'));
        Queue::assertPushed(SendBrandReport::class, fn (SendBrandReport $job): bool => $job->organizationId === $owner->organization_id
            && $job->userId === $owner->id && $job->from === '2026-10-01' && $job->to === '2026-10-31');
    }

    public function test_the_job_e_mails_one_row_per_unit_of_the_organization_and_no_stranger(): void
    {
        // Arrange
        Notification::fake();
        [$owner, $branch] = $this->brandWithTwoUnits();
        $this->sale($owner, '2026-10-05', 5000, commission: 2500);
        $this->sale($branch, '2026-10-07', 7000, commission: 3500);
        $stranger = $this->barbershop('stranger');
        $this->sale($stranger, '2026-10-05', 99900, commission: 50000);

        // Act
        app()->call([new SendBrandReport($owner->organization_id, $owner->id, '2026-10-01', '2026-10-31'), 'handle']);

        // Assert
        Notification::assertSentTo($owner, BrandReportNotification::class, function (BrandReportNotification $notification) use ($owner, $branch): bool {
            $mail = $notification->toMail($owner);
            $csv = (string) ($mail->rawAttachments[0]['data'] ?? '');

            return str_contains($csv, "\xEF\xBB\xBF")
                && str_contains($csv, '50,00;1;50,00;25,00')
                && str_contains($csv, '70,00;1;70,00;35,00')
                && str_contains($csv, '120,00;2;60,00;60,00')
                && ! str_contains($csv, '999,00')
                && $mail->rawAttachments[0]['name'] === 'marca-linkcut_2026-10-01_a_2026-10-31.csv'
                && $owner->tenant_id !== $branch->tenant_id;
        });
    }

    public function test_the_job_sends_nothing_when_the_user_is_no_longer_the_owner_of_that_organization(): void
    {
        // Arrange
        Notification::fake();
        [$owner] = $this->brandWithTwoUnits();
        $stranger = $this->barbershop('stranger');

        // Act
        app()->call([new SendBrandReport($owner->organization_id, $stranger->id, '2026-10-01', '2026-10-31'), 'handle']);
        $owner->update(['role' => UserRole::Barber]);
        app()->call([new SendBrandReport($owner->organization_id, $owner->id, '2026-10-01', '2026-10-31'), 'handle']);

        // Assert
        Notification::assertNothingSent();
    }

    /**
     * The main unit with its owner, plus one branch of the same organization.
     *
     * @return array{0: User, 1: User}
     */
    private function brandWithTwoUnits(): array
    {
        $owner = $this->barbershop('marca');
        $branch = $this->barbershop('marca-filial');
        $organizationId = (int) $owner->organization_id;
        Organization::query()->whereKey($organizationId)->update(['type' => OrganizationType::MultiUnit]);
        Tenant::query()->whereKey($owner->tenant_id)->update(['is_main' => true]);
        Tenant::query()->whereKey($branch->tenant_id)->update(['organization_id' => $organizationId, 'is_main' => false]);

        return [$owner, $branch->refresh()];
    }

    /** A concluded appointment of a unit: the revenue row and its commission snapshot. */
    private function sale(User $unitOwner, string $date, int $priceInCents, int $commission): void
    {
        $this->revenue($unitOwner, ['date' => $date, 'price_in_cents' => $priceInCents]);
        $booking = $this->bookingRequest($unitOwner, $date, '10:00', BookingStatus::Completed);
        app(CurrentTenant::class)->run($unitOwner->tenant_id, fn () => CommissionEntry::query()->create([
            'booking_request_id' => $booking->id,
            'staff_id' => $this->ownerStaffId($unitOwner),
            'date' => $date,
            'base_in_cents' => $priceInCents,
            'percent' => 500,
            'adjustments_json' => [],
            'amount_in_cents' => $commission,
        ]));
    }
}

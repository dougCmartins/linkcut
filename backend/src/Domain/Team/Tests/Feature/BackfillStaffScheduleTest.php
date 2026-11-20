<?php

declare(strict_types=1);

namespace Domain\Team\Tests\Feature;

use Domain\Identity\Enums\UserRole;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Team\Support\BackfillOwnerStaff;
use Domain\Team\Support\BackfillStaffSchedule;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BackfillStaffScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_staff_gets_hours_services_and_existing_bookings_keep_staff(): void
    {
        // Arrange — tenant without staff/hours/services links (as after P3 only).
        $organization = Organization::factory()->create();
        $tenant = Tenant::factory()->create(['organization_id' => $organization->id, 'is_main' => true]);
        $ownerId = DB::table('users')->insertGetId([
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'role' => UserRole::Owner->value,
            'name' => 'Dona',
            'email' => 'dona-p4@example.com',
            'phone' => null,
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organization->update(['owner_user_id' => $ownerId]);

        DB::table('business_hours')->insert([
            'tenant_id' => $tenant->id,
            'weekday' => 1,
            'is_open' => true,
            'opens_at' => '09:00',
            'closes_at' => '19:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $serviceId = DB::table('services')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Corte',
            'duration_minutes' => 45,
            'price_in_cents' => 5000,
            'cost_in_cents' => 600,
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('staff_services')->delete();
        DB::table('staff_business_hours')->delete();
        DB::table('staff_time_blocks')->delete();
        DB::table('staff_permissions')->delete();
        DB::table('staff')->delete();
        BackfillOwnerStaff::run();

        $staffId = (int) DB::table('staff')->where('user_id', $ownerId)->where('tenant_id', $tenant->id)->value('id');
        $this->assertGreaterThan(0, $staffId);

        $bookingId = DB::table('booking_requests')->insertGetId([
            'tenant_id' => $tenant->id,
            'staff_id' => $staffId,
            'client_name' => 'Cliente antigo',
            'client_whatsapp' => '11999999999',
            'service_id' => $serviceId,
            'service_name' => 'Corte',
            'duration_minutes' => 45,
            'date' => '2026-10-01',
            'starts_at' => '10:00',
            'ends_at' => '10:45',
            'status' => BookingStatus::Confirmed->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        BackfillStaffSchedule::run();

        // Assert
        $this->assertSame($staffId, (int) DB::table('booking_requests')->where('id', $bookingId)->value('staff_id'));
        $this->assertTrue(DB::table('staff_services')->where('staff_id', $staffId)->where('service_id', $serviceId)->exists());
        $this->assertTrue(DB::table('staff_business_hours')->where('staff_id', $staffId)->where('weekday', 1)->exists());
    }

    public function test_tenant_without_staff_gets_owner_staff_on_backfill(): void
    {
        // Arrange — branch unit with no staff row (P3 CreateUnit gap).
        $organization = Organization::factory()->create();
        $main = Tenant::factory()->create(['organization_id' => $organization->id, 'is_main' => true, 'slug' => 'main-p4']);
        $branch = Tenant::factory()->create(['organization_id' => $organization->id, 'is_main' => false, 'slug' => 'branch-p4']);
        $ownerId = DB::table('users')->insertGetId([
            'tenant_id' => $main->id,
            'organization_id' => $organization->id,
            'role' => UserRole::Owner->value,
            'name' => 'Dona',
            'email' => 'dona-branch@example.com',
            'phone' => null,
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organization->update(['owner_user_id' => $ownerId]);
        DB::table('staff')->where('tenant_id', $branch->id)->delete();

        // Act
        BackfillStaffSchedule::run();

        // Assert
        $branchStaff = DB::table('staff')->where('tenant_id', $branch->id)->where('user_id', $ownerId)->first();
        $this->assertNotNull($branchStaff);
        $this->assertTrue((bool) $branchStaff->active);
    }
}

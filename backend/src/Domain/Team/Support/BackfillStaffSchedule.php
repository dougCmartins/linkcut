<?php

declare(strict_types=1);

namespace Domain\Team\Support;

use Illuminate\Support\Facades\DB;

/**
 * Ensures every tenant has an owner staff row, seeds hours/services for all staff,
 * and assigns booking_requests.staff_id to that unit's owner staff.
 */
final class BackfillStaffSchedule
{
    public static function run(): void
    {
        self::ensureOwnerStaffOnEveryTenant();
        self::seedHoursAndServicesForAllStaff();
        self::assignBookingRequestsToOwnerStaff();
    }

    private static function ensureOwnerStaffOnEveryTenant(): void
    {
        $now = now();
        $tenants = DB::table('tenants')->orderBy('id')->get(['id', 'organization_id', 'name']);

        foreach ($tenants as $tenant) {
            $hasStaff = DB::table('staff')->where('tenant_id', $tenant->id)->exists();
            if ($hasStaff) {
                continue;
            }

            $owner = DB::table('organizations')
                ->join('users', 'users.id', '=', 'organizations.owner_user_id')
                ->where('organizations.id', $tenant->organization_id)
                ->select(['users.id', 'users.name'])
                ->first();

            if ($owner === null) {
                $owner = DB::table('users')
                    ->where('tenant_id', $tenant->id)
                    ->where('role', 'owner')
                    ->orderBy('id')
                    ->first(['id', 'name']);
            }

            if ($owner === null) {
                continue;
            }

            $staffId = DB::table('staff')->insertGetId([
                'tenant_id' => $tenant->id,
                'user_id' => $owner->id,
                'name' => $owner->name,
                'avatar' => null,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('staff_permissions')->insert([
                'staff_id' => $staffId,
                'see_prices' => false,
                'see_own_commission' => false,
                'see_unit_agenda' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private static function seedHoursAndServicesForAllStaff(): void
    {
        $now = now();
        $staffRows = DB::table('staff')->orderBy('id')->get(['id', 'tenant_id']);

        foreach ($staffRows as $staff) {
            if (! DB::table('staff_business_hours')->where('staff_id', $staff->id)->exists()) {
                $hours = DB::table('business_hours')->where('tenant_id', $staff->tenant_id)->get();
                foreach ($hours as $hour) {
                    DB::table('staff_business_hours')->insert([
                        'tenant_id' => $staff->tenant_id,
                        'staff_id' => $staff->id,
                        'weekday' => $hour->weekday,
                        'is_open' => $hour->is_open,
                        'opens_at' => $hour->opens_at,
                        'closes_at' => $hour->closes_at,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            if (! DB::table('staff_services')->where('staff_id', $staff->id)->exists()) {
                $serviceIds = DB::table('services')->where('tenant_id', $staff->tenant_id)->pluck('id');
                foreach ($serviceIds as $serviceId) {
                    DB::table('staff_services')->insert([
                        'staff_id' => $staff->id,
                        'service_id' => $serviceId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    private static function assignBookingRequestsToOwnerStaff(): void
    {
        $tenants = DB::table('booking_requests')
            ->whereNull('staff_id')
            ->distinct()
            ->pluck('tenant_id');

        foreach ($tenants as $tenantId) {
            $staffId = self::ownerStaffIdForTenant((int) $tenantId);
            if ($staffId === null) {
                continue;
            }

            DB::table('booking_requests')
                ->where('tenant_id', $tenantId)
                ->whereNull('staff_id')
                ->update(['staff_id' => $staffId]);
        }
    }

    private static function ownerStaffIdForTenant(int $tenantId): ?int
    {
        $ownerUserId = DB::table('tenants')
            ->join('organizations', 'organizations.id', '=', 'tenants.organization_id')
            ->where('tenants.id', $tenantId)
            ->value('organizations.owner_user_id');

        if ($ownerUserId) {
            $id = DB::table('staff')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $ownerUserId)
                ->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        $id = DB::table('staff')
            ->where('tenant_id', $tenantId)
            ->where('active', true)
            ->orderBy('id')
            ->value('id');

        return $id ? (int) $id : null;
    }
}

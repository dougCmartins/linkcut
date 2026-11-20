<?php

declare(strict_types=1);

namespace Domain\Team\Support;

use Illuminate\Support\Facades\DB;

/** Copies unit hours/services onto a staff member (or attaches one service to every staff of the unit). */
final class SeedStaffSchedule
{
    public static function forStaff(int $tenantId, int $staffId): void
    {
        $now = now();

        if (! DB::table('staff_business_hours')->where('staff_id', $staffId)->exists()) {
            $hours = DB::table('business_hours')->where('tenant_id', $tenantId)->get();
            foreach ($hours as $hour) {
                DB::table('staff_business_hours')->insert([
                    'tenant_id' => $tenantId,
                    'staff_id' => $staffId,
                    'weekday' => $hour->weekday,
                    'is_open' => $hour->is_open,
                    'opens_at' => $hour->opens_at,
                    'closes_at' => $hour->closes_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $existing = DB::table('staff_services')->where('staff_id', $staffId)->pluck('service_id')->all();
        $serviceIds = DB::table('services')->where('tenant_id', $tenantId)->pluck('id');
        foreach ($serviceIds as $serviceId) {
            if (in_array($serviceId, $existing, true)) {
                continue;
            }
            DB::table('staff_services')->insert([
                'staff_id' => $staffId,
                'service_id' => $serviceId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public static function attachServiceToAllStaff(int $tenantId, int $serviceId): void
    {
        $now = now();
        $staffIds = DB::table('staff')->where('tenant_id', $tenantId)->pluck('id');
        foreach ($staffIds as $staffId) {
            $exists = DB::table('staff_services')
                ->where('staff_id', $staffId)
                ->where('service_id', $serviceId)
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('staff_services')->insert([
                'staff_id' => $staffId,
                'service_id' => $serviceId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}

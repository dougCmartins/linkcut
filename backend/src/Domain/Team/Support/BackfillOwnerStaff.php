<?php

declare(strict_types=1);

namespace Domain\Team\Support;

use Illuminate\Support\Facades\DB;

/**
 * One staff row (with default permissions) per existing owner, linked to their user and home unit.
 * Used by the team migration and its regression test.
 */
final class BackfillOwnerStaff
{
    public static function run(): void
    {
        $owners = DB::table('users')->where('role', 'owner')->orderBy('id')->get(['id', 'tenant_id', 'name']);
        $now = now();

        foreach ($owners as $owner) {
            $exists = DB::table('staff')
                ->where('tenant_id', $owner->tenant_id)
                ->where('user_id', $owner->id)
                ->exists();
            if ($exists) {
                continue;
            }

            $staffId = DB::table('staff')->insertGetId([
                'tenant_id' => $owner->tenant_id,
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
}

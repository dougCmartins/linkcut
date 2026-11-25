<?php

declare(strict_types=1);

namespace Domain\Tenant\Support;

use Domain\Tenant\Enums\OrganizationType;
use Illuminate\Support\Facades\DB;

/**
 * One individual organization per existing tenant (main unit), without changing tenant slugs.
 * Used by the organizations migration and its regression test.
 */
final class BackfillOrganizations
{
    public static function run(): void
    {
        $tenants = DB::table('tenants')->whereNull('organization_id')->orderBy('id')->get();
        foreach ($tenants as $tenant) {
            $ownerId = DB::table('users')
                ->where('tenant_id', $tenant->id)
                ->where('role', 'owner')
                ->orderBy('id')
                ->value('id')
                ?? DB::table('users')->where('tenant_id', $tenant->id)->orderBy('id')->value('id');

            $organizationId = DB::table('organizations')->insertGetId([
                'owner_user_id' => $ownerId,
                'type' => OrganizationType::Individual->value,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('tenants')->where('id', $tenant->id)->update([
                'organization_id' => $organizationId,
                'is_main' => true,
            ]);

            DB::table('users')->where('tenant_id', $tenant->id)->update([
                'organization_id' => $organizationId,
            ]);
        }
    }
}

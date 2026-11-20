<?php

declare(strict_types=1);

namespace Domain\Team\Tests\Feature;

use Domain\Identity\Enums\UserRole;
use Domain\Team\Support\BackfillOwnerStaff;
use Domain\Tenant\Models\Organization;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BackfillOwnerStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_owner_gets_a_staff_row_linked_to_their_user(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $tenant = Tenant::factory()->create(['organization_id' => $organization->id, 'is_main' => true]);
        $ownerId = DB::table('users')->insertGetId([
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'role' => UserRole::Owner->value,
            'name' => 'Dona',
            'email' => 'dona@example.com',
            'phone' => null,
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('staff')->delete();
        DB::table('staff_permissions')->delete();

        // Act
        BackfillOwnerStaff::run();

        // Assert
        $staff = DB::table('staff')->where('user_id', $ownerId)->first();
        $this->assertNotNull($staff);
        $this->assertSame($tenant->id, $staff->tenant_id);
        $this->assertSame('Dona', $staff->name);
        $this->assertTrue((bool) $staff->active);
        $permissions = DB::table('staff_permissions')->where('staff_id', $staff->id)->first();
        $this->assertNotNull($permissions);
        $this->assertFalse((bool) $permissions->see_prices);
        $this->assertFalse((bool) $permissions->see_own_commission);
        $this->assertFalse((bool) $permissions->see_unit_agenda);
    }
}

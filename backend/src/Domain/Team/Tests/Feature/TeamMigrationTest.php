<?php

declare(strict_types=1);

namespace Domain\Team\Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Every unit that exists before the team gets its first barber: its owner (docs/decisoes/0001 P3). */
final class TeamMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_of_each_existing_barbershop_becomes_its_first_barber_with_everything_off(): void
    {
        // Arrange: the database before the team, with an owner and an old barber row
        $migration = require database_path('migrations/2026_11_19_100000_create_team_tables.php');
        assert($migration instanceof Migration);
        $migration->down();
        $now = now();
        $tenant = DB::table('tenants')->insertGetId(['name' => 'Rafael', 'slug' => 'rafael', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $owner = DB::table('users')->insertGetId(['tenant_id' => $tenant, 'role' => 'owner', 'name' => 'Rafael Costa', 'email' => 'rafael@test.dev', 'password' => 'x', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('users')->insert(['tenant_id' => $tenant, 'role' => 'barber', 'name' => 'Velho', 'email' => 'velho@test.dev', 'password' => 'x', 'created_at' => $now, 'updated_at' => $now]);

        // Act
        $migration->up();

        // Assert
        $staff = DB::table('staff')->sole();
        $this->assertSame([$tenant, $owner, 'Rafael Costa', 'rafael@test.dev'], [(int) $staff->tenant_id, (int) $staff->user_id, $staff->name, $staff->email]);
        $permissions = DB::table('staff_permissions')->sole();
        $this->assertSame([0, 0, 0], [(int) $permissions->see_prices, (int) $permissions->see_own_commission, (int) $permissions->see_unit_agenda]);
    }
}

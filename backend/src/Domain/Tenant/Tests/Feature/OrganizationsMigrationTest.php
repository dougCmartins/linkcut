<?php

declare(strict_types=1);

namespace Domain\Tenant\Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Barbershops that exist before branches become individual organizations, with no link changed (docs/decisoes/0001 P1). */
final class OrganizationsMigrationTest extends TestCase
{
    // Not RefreshDatabase: SQLite only turns foreign keys off outside a transaction.
    use DatabaseMigrations;

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_17_100000_create_organizations_table.php');
    }

    public function test_each_existing_barbershop_becomes_an_individual_organization_owned_by_its_owner(): void
    {
        // Arrange: the database as it was before the migration, with two barbershops
        $migration = $this->migration();
        Schema::withoutForeignKeyConstraints(fn () => $migration->down());
        $now = now();
        $rafael = DB::table('tenants')->insertGetId(['name' => 'Rafael', 'slug' => 'rafael', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $bruno = DB::table('tenants')->insertGetId(['name' => 'Bruno', 'slug' => 'bruno', 'active' => false, 'created_at' => $now, 'updated_at' => $now]);
        $user = fn (int $tenant, string $role, string $email): int => DB::table('users')->insertGetId([
            'tenant_id' => $tenant, 'role' => $role, 'name' => $email, 'email' => $email, 'password' => 'x', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $rafaelOwner = $user($rafael, 'owner', 'rafael@test.dev');
        $rafaelBarber = $user($rafael, 'barber', 'joao@test.dev');
        $brunoOwner = $user($bruno, 'owner', 'bruno@test.dev');

        // Act (SQLite rebuilds a table to add a foreign key, so the checks wait; MySQL in production alters in place)
        Schema::withoutForeignKeyConstraints(fn () => $migration->up());

        // Assert
        $organizations = DB::table('organizations')->orderBy('id')->get();
        $this->assertCount(2, $organizations);
        $this->assertSame(['rafael', 'bruno'], $organizations->pluck('slug')->all());
        $this->assertSame(['individual', 'individual'], $organizations->pluck('type')->all());
        $this->assertSame([$rafaelOwner, $brunoOwner], $organizations->pluck('owner_user_id')->map(fn ($id): int => (int) $id)->all());
        $tenants = DB::table('tenants')->orderBy('id')->get();
        $this->assertSame(['rafael', 'bruno'], $tenants->pluck('slug')->all());
        $this->assertSame([(int) $organizations[0]->id, (int) $organizations[1]->id], $tenants->pluck('organization_id')->map(fn ($id): int => (int) $id)->all());
        $this->assertSame([1, 1], $tenants->pluck('is_main')->map(fn ($main): int => (int) $main)->all());
        $this->assertSame((int) $organizations[0]->id, (int) DB::table('users')->where('id', $rafaelBarber)->value('organization_id'));
    }
}

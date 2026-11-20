<?php

declare(strict_types=1);

namespace Domain\Tenant\Tests\Feature;

use Domain\Identity\Enums\UserRole;
use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Support\BackfillOrganizations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class BackfillOrganizationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_legacy_tenant_gets_an_individual_organization_without_changing_slugs(): void
    {
        // Arrange — rows as they looked before organizations existed
        Schema::disableForeignKeyConstraints();
        DB::table('users')->delete();
        DB::table('tenants')->delete();
        DB::table('organizations')->delete();
        Schema::enableForeignKeyConstraints();

        $tenantId = DB::table('tenants')->insertGetId([
            'organization_id' => null,
            'is_main' => false,
            'name' => 'Navalha',
            'slug' => 'navalha',
            'active' => true,
            'timezone' => 'America/Sao_Paulo',
            'onboarded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $ownerId = DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_id' => null,
            'role' => UserRole::Owner->value,
            'name' => 'Rafael',
            'email' => 'rafael@example.com',
            'phone' => null,
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        BackfillOrganizations::run();

        // Assert
        $organization = DB::table('organizations')->where('slug', 'navalha')->first();
        $this->assertNotNull($organization);
        $this->assertSame(OrganizationType::Individual->value, $organization->type);
        $this->assertSame('Navalha', $organization->name);
        $this->assertSame($ownerId, $organization->owner_user_id);
        $this->assertSame('navalha', DB::table('tenants')->where('id', $tenantId)->value('slug'));
        $this->assertSame($organization->id, DB::table('tenants')->where('id', $tenantId)->value('organization_id'));
        $this->assertTrue((bool) DB::table('tenants')->where('id', $tenantId)->value('is_main'));
        $this->assertSame($organization->id, DB::table('users')->where('id', $ownerId)->value('organization_id'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\BranchDemoSeeder;
use Database\Seeders\DatabaseSeeder;
use Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use RuntimeException;
use Tests\TestCase;

/** Demo barbershops have a known password: the seeder refuses to run in production. */
final class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_seeder_does_not_run_in_production(): void
    {
        // Arrange
        $this->app['env'] = 'production';

        // Assert
        $this->expectException(RuntimeException::class);

        // Act
        app(DatabaseSeeder::class)->run();
    }

    public function test_the_branch_demo_gives_an_owner_with_two_units_and_a_barber_on_the_branch(): void
    {
        // Arrange
        app(BranchDemoSeeder::class)->run();

        // Act
        app(BranchDemoSeeder::class)->run();
        $owner = User::query()->where('email', 'dona@cortefino.app')->sole();
        $barber = User::query()->where('email', 'barbeiro@cortefino.app')->sole();
        Passport::actingAs($owner);
        $organization = $this->getJson('/api/organization');
        $branchId = collect($organization->json('units'))->firstWhere('slug', 'corte-fino-paulista')['id'];
        $team = $this->getJson('/api/team/staff', ['X-Unit' => (string) $branchId]);
        $this->app['auth']->forgetGuards();
        $staff = $this->getJson('/api/public/corte-fino-paulista/staff');

        // Assert
        $organization->assertOk()->assertJsonPath('type', 'multi_unit')->assertJsonCount(2, 'units');
        $team->assertOk()->assertJsonCount(2);
        $staff->assertOk()->assertJsonCount(2);
        $this->assertSame($branchId, $barber->tenant_id);
        $this->assertTrue(password_verify('linkcut123', $barber->password));
    }
}

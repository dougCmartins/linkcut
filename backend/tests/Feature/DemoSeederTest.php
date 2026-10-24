<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

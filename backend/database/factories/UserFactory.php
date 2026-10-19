<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
final class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'role' => UserRole::Owner,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+55119'.fake()->numerify('########'),
            'password' => 'password',
            'two_factor_channel' => null,
        ];
    }

    public function withoutPhone(): static
    {
        return $this->state(['phone' => null]);
    }
}

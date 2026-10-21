<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Tenant> */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'active' => true,
            'onboarded_at' => now(),
        ];
    }

    /** Just signed up: the wizard is not done yet. */
    public function notOnboarded(): static
    {
        return $this->state(['onboarded_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}

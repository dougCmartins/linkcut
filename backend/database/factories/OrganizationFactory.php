<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Tenant\Enums\OrganizationType;
use Domain\Tenant\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Organization> */
final class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'owner_user_id' => null,
            'type' => OrganizationType::Individual,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
        ];
    }

    public function multiUnit(): static
    {
        return $this->state(['type' => OrganizationType::MultiUnit]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Tenant\Models\Organization;
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

    /** Like a sign-up: a tenant without an organization gets its own (individual, same name and slug). */
    public function configure(): static
    {
        return $this->afterCreating(function (Tenant $tenant): void {
            if ($tenant->organization_id !== null) {
                return;
            }
            $organization = Organization::factory()->create(['name' => $tenant->name, 'slug' => $tenant->slug]);
            $tenant->forceFill(['organization_id' => $organization->id, 'is_main' => true])->save();
        });
    }

    /** A branch of an organization (not its main unit). */
    public function branchOf(Organization $organization): static
    {
        return $this->state(['organization_id' => $organization->id, 'is_main' => false]);
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

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

    /** The user belongs to the organization of their unit; an owner without organization owner becomes it. */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $tenant = Tenant::query()->with('organization')->find($user->tenant_id);
            $organization = $tenant?->organization;
            if (! $organization) {
                return;
            }
            $user->forceFill(['organization_id' => $organization->id])->save();
            if ($user->role === UserRole::Owner && $organization->owner_user_id === null) {
                $organization->forceFill(['owner_user_id' => $user->id])->save();
            }
        });
    }

    public function withoutPhone(): static
    {
        return $this->state(['phone' => null]);
    }
}

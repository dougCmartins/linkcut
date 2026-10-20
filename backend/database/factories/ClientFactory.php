<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Client\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Client> */
final class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '11912345678',
            'password' => null,
        ];
    }

    /** Completed the sign-up (password "segredo123", e-mail confirmed). */
    public function registered(): self
    {
        return $this->state(['password' => 'segredo123', 'email_verified_at' => now()]);
    }
}

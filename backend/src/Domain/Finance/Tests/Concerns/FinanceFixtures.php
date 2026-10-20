<?php

declare(strict_types=1);

namespace Domain\Finance\Tests\Concerns;

use Carbon\CarbonImmutable;
use Domain\Finance\Models\RevenueEntry;
use Domain\Identity\Models\User;
use Domain\Tenant\Context\CurrentTenant;
use Laravel\Passport\Passport;

trait FinanceFixtures
{
    /** Signs in a barber of a new barbershop (America/Sao_Paulo). */
    protected function actingAsBarber(): User
    {
        $this->app['auth']->forgetGuards();
        $user = User::factory()->create();
        Passport::actingAs($user);

        return $user;
    }

    /** Freezes "now" at a local time of the barbershop. */
    protected function travelToLocal(string $dateTime): void
    {
        $this->travelTo(CarbonImmutable::parse($dateTime, 'America/Sao_Paulo'));
    }

    /** @param array<string, mixed> $attributes */
    protected function revenue(User $owner, array $attributes): RevenueEntry
    {
        return app(CurrentTenant::class)->run($owner->tenant_id, fn () => RevenueEntry::query()->create([
            'time' => '10:00',
            'client_name' => 'Lucas Andrade',
            'service_name' => 'Corte',
            'price_in_cents' => 5000,
            'cost_in_cents' => 600,
            ...$attributes,
        ]));
    }
}

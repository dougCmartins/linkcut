<?php

declare(strict_types=1);

namespace Domain\Schedule\Tests\Concerns;

use Carbon\CarbonImmutable;
use Domain\Identity\Models\User;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Models\BookingRequest;
use Domain\Settings\Actions\CreateDefaultSettings;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\Service;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Models\Tenant;
use Laravel\Passport\Passport;

/**
 * A barbershop with the default settings: Corte 45 min (R$ 50, cost R$ 6), Barba 30 min (R$ 40, cost R$ 4),
 * Sun closed, Mon–Fri 09–19, Sat 09–14, every 30 min, 7-day window, address in São Paulo.
 * "Now" is Monday 2026-10-19 08:00 local.
 */
trait ScheduleFixtures
{
    /** Rua Augusta, 1500 – São Paulo (the point used to measure the clients' distance). */
    protected const SHOP_ADDRESS = [
        'address_zip_code' => '01304001',
        'address_street' => 'Rua Augusta',
        'address_number' => '1500',
        'address_district' => 'Consolação',
        'address_city' => 'São Paulo',
        'address_state' => 'SP',
        'latitude' => -23.5578,
        'longitude' => -46.6609,
    ];

    protected function freezeMondayMorning(string $time = '08:00'): void
    {
        $this->travelTo(CarbonImmutable::parse("2026-10-19 {$time}", 'America/Sao_Paulo'));
    }

    protected function barbershop(string $slug = 'rafael'): User
    {
        $tenant = Tenant::factory()->create(['slug' => $slug]);
        app(CurrentTenant::class)->run($tenant->id, function (): void {
            app(CreateDefaultSettings::class)->handle(new DefaultSettingsData);
            ProfileSettings::query()->firstOrFail()->update(self::SHOP_ADDRESS);
        });

        return User::factory()->create(['tenant_id' => $tenant->id]);
    }

    protected function actingAsOwner(User $owner): void
    {
        $this->app['auth']->forgetGuards();
        Passport::actingAs($owner);
    }

    /** Service id by name ("Corte" or "Barba"). */
    protected function serviceId(User $owner, string $name): int
    {
        return app(CurrentTenant::class)->run($owner->tenant_id, fn () => Service::query()->where('name', $name)->value('id'));
    }

    /** @param array<string, mixed> $attributes e.g. client_id, client_email */
    protected function bookingRequest(User $owner, string $date, string $startsAt, BookingStatus $status = BookingStatus::Pending, string $service = 'Corte', array $attributes = []): BookingRequest
    {
        return app(CurrentTenant::class)->run($owner->tenant_id, function () use ($owner, $date, $startsAt, $status, $service, $attributes): BookingRequest {
            $duration = $service === 'Corte' ? 45 : 30;

            return BookingRequest::query()->create([
                'client_name' => 'Ana Souza',
                'client_whatsapp' => '11912345678',
                'service_id' => $this->serviceId($owner, $service),
                'service_name' => $service,
                'duration_minutes' => $duration,
                'date' => $date,
                'starts_at' => $startsAt,
                'ends_at' => CarbonImmutable::createFromFormat('H:i', $startsAt)->addMinutes($duration)->format('H:i'),
                'status' => $status,
                ...$attributes,
            ]);
        });
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Models\BookingRequest;
use Domain\Settings\Models\Service;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Context\TenantClock;
use Domain\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;

/** A few booking requests for the demo account (pending ones and one confirmed for today), only when it has none. */
final class ScheduleDemoSeeder extends Seeder
{
    public function forTenant(Tenant $tenant): void
    {
        app(CurrentTenant::class)->run($tenant->id, function (): void {
            if (BookingRequest::query()->exists()) {
                return;
            }
            $today = app(TenantClock::class)->today();
            $services = Service::query()->ordered()->get();
            $haircut = $services->first();
            $beard = $services->get(1, $haircut);

            $this->request('Lucas Andrade', '11988887777', $haircut, $today, '18:00', BookingStatus::Confirmed);
            $this->request('Bruno Lima', '11977776666', $beard, $today->addDay(), '10:00', BookingStatus::Pending);
            $this->request('Pedro Martins', '11966665555', $haircut, $today->addDay(), '16:30', BookingStatus::Pending);
        });
    }

    private function request(string $client, string $whatsapp, Service $service, CarbonImmutable $date, string $time, BookingStatus $status): void
    {
        BookingRequest::query()->create([
            'client_name' => $client,
            'client_whatsapp' => $whatsapp,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'duration_minutes' => $service->duration_minutes,
            'date' => $date->toDateString(),
            'starts_at' => $time,
            'ends_at' => CarbonImmutable::createFromFormat('H:i', $time)->addMinutes($service->duration_minutes)->format('H:i'),
            'status' => $status,
        ]);
    }
}

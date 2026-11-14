<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAppointments\Actions;

use Domain\Catalog\Data\PublicProductData;
use Domain\Client\Models\Client;
use Domain\Orchestrator\Catalog\Actions\PresentPublicProducts;
use Domain\Schedule\Actions\ListClientBookings;
use Domain\Schedule\Data\ClientBookingData;
use Domain\Schedule\Data\ClientBookingsQueryData;
use Domain\Settings\Support\PublicSettingsCache;
use Domain\Tenant\Actions\FindActiveTenantBySlug;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\TenantSlugData;
use Domain\Tenant\Exceptions\TenantNotFoundException;
use Spatie\LaravelData\DataCollection;

/**
 * Active products of one barbershop, for a client who already booked there.
 * A missing shop and a shop they never booked answer the same 404.
 */
final class ListClientBarbershopProducts
{
    public function __construct(
        private readonly FindActiveTenantBySlug $findTenant,
        private readonly ListClientBookings $listBookings,
        private readonly PresentPublicProducts $presentProducts,
        private readonly PublicSettingsCache $cache,
        private readonly CurrentTenant $currentTenant,
    ) {}

    /** @return DataCollection<int, PublicProductData> */
    public function handle(TenantSlugData $slug): DataCollection
    {
        $tenant = $this->findTenant->handle($slug);
        /** @var Client $client */
        $client = auth('client')->user();
        $bookings = $this->listBookings->handle(new ClientBookingsQueryData(client_id: $client->id));
        if (! $this->bookedAt($bookings, $tenant->id)) {
            throw new TenantNotFoundException;
        }

        return $this->currentTenant->run($tenant->id, function (): DataCollection {
            return $this->cache->remember(
                PublicSettingsCache::PRODUCTS,
                fn (): DataCollection => $this->presentProducts->handle(),
            );
        });
    }

    /** @param DataCollection<int, ClientBookingData> $bookings */
    private function bookedAt(DataCollection $bookings, int $tenantId): bool
    {
        foreach ($bookings as $booking) {
            if ($booking->tenant_id === $tenantId) {
                return true;
            }
        }

        return false;
    }
}

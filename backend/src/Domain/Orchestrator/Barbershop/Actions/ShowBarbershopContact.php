<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Barbershop\Actions;

use Domain\Orchestrator\Barbershop\Data\BarbershopContactData;
use Domain\Settings\Actions\ShowProfileSettings;
use Domain\Settings\Data\AddressData;
use Domain\Tenant\Actions\ShowCurrentTenant;

/** Name, address, map, WhatsApp and page of the current barbershop (Tenant + Settings). */
final class ShowBarbershopContact
{
    public function __construct(
        private readonly ShowCurrentTenant $showTenant,
        private readonly ShowProfileSettings $showProfile,
    ) {}

    public function handle(): BarbershopContactData
    {
        $tenant = $this->showTenant->handle();
        $profile = $this->showProfile->handle();
        $address = $profile->address ? self::formatAddress($profile->address) : '';

        return new BarbershopContactData(
            name: $profile->name !== '' ? $profile->name : $tenant->name,
            slug: $tenant->slug,
            address: $address,
            maps_url: $profile->address ? self::mapsUrl($profile->address, $address) : '',
            whatsapp_url: $profile->whatsapp_number !== '' ? 'https://wa.me/55'.$profile->whatsapp_number : '',
            booking_url: rtrim((string) config('app.frontend_url'), '/').'/'.$tenant->slug,
        );
    }

    /** Same format as the SPA (utils/address.ts). */
    private static function formatAddress(AddressData $address): string
    {
        $street = collect([$address->street, $address->number, $address->complement])
            ->map(fn (?string $part): string => trim((string) $part))->filter()->implode(', ');

        return "{$street} – {$address->district}, {$address->city}/{$address->state}";
    }

    /** Google Maps search (no key needed): the exact point when known, else the address. */
    private static function mapsUrl(AddressData $address, string $formatted): string
    {
        $query = $address->latitude !== null && $address->longitude !== null
            ? "{$address->latitude},{$address->longitude}"
            : "{$formatted}, {$address->zip_code}";

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);
    }
}

<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Barbershop\Actions;

use Domain\Orchestrator\Barbershop\Data\BarbershopContactData;
use Domain\Settings\Actions\ListContactProfiles;
use Domain\Settings\Data\AddressData;
use Domain\Settings\Data\ContactProfileData;
use Domain\Tenant\Actions\ListTenantsByIds;
use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Data\TenantIdsData;

/**
 * Name, address, map, WhatsApp and page of several barbershops (Tenant + Settings), in three queries
 * whatever their number: the client area and the e-mails never read one barbershop at a time.
 */
final class ListBarbershopContacts
{
    public function __construct(
        private readonly ListTenantsByIds $listTenants,
        private readonly ListContactProfiles $listProfiles,
    ) {}

    /** @return array<int, BarbershopContactData> keyed by tenant id */
    public function handle(TenantIdsData $data): array
    {
        if ($data->ids === []) {
            return [];
        }
        $profiles = collect($this->listProfiles->handle($data)->all())->keyBy(fn (ContactProfileData $profile): int => $profile->tenant_id);

        return collect($this->listTenants->handle($data)->all())
            ->mapWithKeys(fn (TenantData $tenant): array => [$tenant->id => self::contact($tenant, $profiles->get($tenant->id))])
            ->all();
    }

    private static function contact(TenantData $tenant, ?ContactProfileData $profile): BarbershopContactData
    {
        $address = $profile?->address;
        $formatted = $address ? self::formatAddress($address) : '';
        $whatsapp = $profile?->whatsapp_number ?? '';

        return new BarbershopContactData(
            name: $profile && $profile->name !== '' ? $profile->name : $tenant->name,
            slug: $tenant->slug,
            address: $formatted,
            maps_url: $address ? self::mapsUrl($address, $formatted) : '',
            whatsapp_url: $whatsapp !== '' ? 'https://wa.me/55'.$whatsapp : '',
            booking_url: rtrim((string) config('app.frontend_url'), '/').'/'.$tenant->slug,
            avatar_url: $profile?->avatar_url,
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

<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\AddressInputData;
use Domain\Settings\Support\AddressGeocoder;

/**
 * The point on the map of an address: the one sent ("Estou na barbearia") or looked up. Called before a transaction
 * opens, since the lookup is a web service. Without a result, the point stays empty (the barber can fix it later).
 */
final class LocateAddress
{
    public function __construct(private readonly AddressGeocoder $geocoder) {}

    public function handle(AddressInputData $address): AddressInputData
    {
        if ($address->latitude !== null && $address->longitude !== null) {
            return $address;
        }
        $point = $this->geocoder->locate($address);

        return $address->at($point['latitude'] ?? null, $point['longitude'] ?? null);
    }
}

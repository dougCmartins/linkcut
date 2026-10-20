<?php

declare(strict_types=1);

namespace Domain\Settings\Support;

use Domain\Settings\Data\AddressInputData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Finds the point on the map of an address with OpenStreetMap Nominatim (free, no key).
 * Usage policy: identify the app (User-Agent) and at most 1 request per second, so it is only called
 * when the barber saves a changed address. Returns null when nothing is found or the service fails.
 */
final class AddressGeocoder
{
    /** @return array{latitude: float, longitude: float}|null */
    public function locate(AddressInputData $address): ?array
    {
        // Full address first; without number and district when that finds nothing.
        $queries = [
            "{$address->street}, {$address->number}, {$address->district}, {$address->city} - {$address->state}, {$address->zip_code}, Brasil",
            "{$address->street}, {$address->city} - {$address->state}, Brasil",
        ];

        foreach ($queries as $query) {
            $point = $this->search($query);
            if ($point) {
                return $point;
            }
        }
        Log::warning('settings_address_not_located', ['city' => $address->city, 'state' => $address->state]);

        return null;
    }

    /** @return array{latitude: float, longitude: float}|null */
    private function search(string $query): ?array
    {
        try {
            $response = Http::baseUrl((string) config('services.nominatim.url'))
                ->withHeaders(['User-Agent' => (string) config('services.nominatim.user_agent')])
                ->timeout(5)
                ->get('/search', ['q' => $query, 'format' => 'jsonv2', 'limit' => 1, 'countrycodes' => 'br']);
        } catch (Throwable $exception) {
            Log::warning('settings_geocoder_failed', ['message' => $exception->getMessage()]);

            return null;
        }

        $first = $response->successful() ? ($response->json()[0] ?? null) : null;

        return $first ? ['latitude' => (float) $first['lat'], 'longitude' => (float) $first['lon']] : null;
    }
}

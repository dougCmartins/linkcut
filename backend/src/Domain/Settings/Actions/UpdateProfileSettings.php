<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\AddressInputData;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Data\ProfileSettingsData;
use Domain\Settings\Data\SocialLinkData;
use Domain\Settings\Data\UpdateProfileSettingsData;
use Domain\Settings\Exceptions\ImageNotFoundException;
use Domain\Settings\Models\Image;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\SocialLink;
use Domain\Settings\Support\AddressGeocoder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/** Saves the "Link" tab: profile texts, avatar/background, social links and the barbershop address. */
final class UpdateProfileSettings
{
    public function __construct(
        private readonly CreateDefaultSettings $createDefaultSettings,
        private readonly ShowProfileSettings $showProfile,
        private readonly AddressGeocoder $geocoder,
    ) {}

    public function handle(UpdateProfileSettingsData $data): ProfileSettingsData
    {
        $this->ensureValid($data);
        $this->createDefaultSettings->handle(new DefaultSettingsData);
        $point = $this->point($data->address, ProfileSettings::query()->firstOrFail());

        DB::transaction(function () use ($data, $point): void {
            ProfileSettings::query()->firstOrFail()->update([
                ...$this->addressColumns($data->address),
                'latitude' => $point['latitude'] ?? null,
                'longitude' => $point['longitude'] ?? null,
                'name' => $data->name,
                'handle' => $data->handle ?? '',
                'bio' => $data->bio ?? '',
                'avatar_image_id' => $data->avatar_image_id,
                'background_image_id' => $data->background_image_id,
                'whatsapp_number' => preg_replace('/\D/', '', $data->whatsapp_number),
                'whatsapp_message' => $data->whatsapp_message ?? '',
            ]);

            SocialLink::query()->delete();
            foreach ($data->socials as $position => $social) {
                /** @var SocialLinkData $social */
                SocialLink::query()->create(['network' => $social->network, 'url' => $social->url, 'position' => $position]);
            }
        });

        Log::info('settings_profile_updated');

        return $this->showProfile->handle();
    }

    /**
     * Point on the map: the device location from "Estou na barbearia" when sent; the current one when the
     * address did not change; otherwise looked up again (outside the transaction: it calls a web service).
     *
     * @return array{latitude: float, longitude: float}|null
     */
    private function point(AddressInputData $address, ProfileSettings $current): ?array
    {
        if ($address->latitude !== null && $address->longitude !== null) {
            return ['latitude' => $address->latitude, 'longitude' => $address->longitude];
        }
        $unchanged = array_diff_assoc($this->addressColumns($address), $current->only(array_keys($this->addressColumns($address)))) === [];
        if ($unchanged && $current->latitude !== null && $current->longitude !== null) {
            return ['latitude' => $current->latitude, 'longitude' => $current->longitude];
        }

        return $this->geocoder->locate($address);
    }

    /** @return array<string, string|null> */
    private function addressColumns(AddressInputData $address): array
    {
        return [
            'address_zip_code' => $address->zip_code,
            'address_street' => trim($address->street),
            'address_number' => trim($address->number),
            'address_complement' => $address->complement ? trim($address->complement) : null,
            'address_district' => trim($address->district),
            'address_city' => trim($address->city),
            'address_state' => $address->state,
        ];
    }

    private function ensureValid(UpdateProfileSettingsData $data): void
    {
        $networks = array_map(fn (SocialLinkData $social): string => $social->network->value, $data->socials->all());
        foreach (array_diff_assoc($networks, array_unique($networks)) as $index => $network) {
            throw ValidationException::withMessages(["socials.{$index}.network" => 'distinct']);
        }

        if (Image::missingIds([(int) $data->avatar_image_id, (int) $data->background_image_id])) {
            throw new ImageNotFoundException;
        }
    }
}

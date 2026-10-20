<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\AddressData;
use Domain\Settings\Data\ImageData;
use Domain\Settings\Data\ProfileSettingsData;
use Domain\Settings\Data\SocialLinkData;
use Domain\Settings\Models\Image;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\SocialLink;
use Spatie\LaravelData\DataCollection;

final class ShowProfileSettings
{
    public function handle(): ProfileSettingsData
    {
        $profile = ProfileSettings::query()->with(['avatar', 'background'])->firstOrFail();
        $socials = SocialLink::query()->orderBy('position')->get()
            ->map(fn (SocialLink $link): SocialLinkData => new SocialLinkData(network: $link->network, url: $link->url))
            ->all();

        return new ProfileSettingsData(
            name: $profile->name,
            handle: $profile->handle,
            bio: $profile->bio,
            avatar: $this->image($profile->avatar),
            background: $this->image($profile->background),
            whatsapp_number: $profile->whatsapp_number,
            whatsapp_message: $profile->whatsapp_message,
            socials: new DataCollection(SocialLinkData::class, $socials),
            address: $this->address($profile),
        );
    }

    private function address(ProfileSettings $profile): ?AddressData
    {
        return $profile->hasAddress() ? new AddressData(
            zip_code: $profile->address_zip_code,
            street: $profile->address_street,
            number: $profile->address_number,
            complement: $profile->address_complement,
            district: $profile->address_district,
            city: $profile->address_city,
            state: $profile->address_state,
            latitude: $profile->latitude,
            longitude: $profile->longitude,
        ) : null;
    }

    private function image(?Image $image): ?ImageData
    {
        return $image ? new ImageData(id: $image->id, url: $image->url()) : null;
    }
}

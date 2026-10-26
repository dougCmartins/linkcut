<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** Bio link as a visitor sees it: only networks with a url. */
final class PublicProfileData extends Data
{
    /**
     * @param  DataCollection<int, SocialLinkData>  $socials
     * @param  DataCollection<int, ProfileLinkData>  $links
     */
    public function __construct(
        public readonly string $name,
        public readonly string $handle,
        public readonly string $bio,
        public readonly ?string $avatar_url,
        public readonly ?string $background_url,
        public readonly string $whatsapp_number,
        public readonly string $whatsapp_message,
        #[DataCollectionOf(SocialLinkData::class)]
        public readonly DataCollection $socials,
        #[DataCollectionOf(ProfileLinkData::class)]
        public readonly DataCollection $links,
        /** Where the barbershop is (a business address is public). Null = it can't receive bookings yet. */
        public readonly ?AddressData $address,
    ) {}
}

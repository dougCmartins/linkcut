<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** Bio link profile, as the editor sees it. */
final class ProfileSettingsData extends Data
{
    /** @param DataCollection<int, SocialLinkData> $socials */
    public function __construct(
        public readonly string $name,
        public readonly string $handle,
        public readonly string $bio,
        public readonly ?ImageData $avatar,
        public readonly ?ImageData $background,
        public readonly string $whatsapp_number,
        public readonly string $whatsapp_message,
        #[DataCollectionOf(SocialLinkData::class)]
        public readonly DataCollection $socials,
    ) {}
}

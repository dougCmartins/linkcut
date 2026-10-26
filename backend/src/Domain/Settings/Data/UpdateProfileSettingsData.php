<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Domain\Settings\Support\WhatsappNumberRule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** "Link" tab, saved as a whole. Images are referenced by the id returned by the upload. */
final class UpdateProfileSettingsData extends Data
{
    /**
     * @param  DataCollection<int, SocialLinkData>  $socials
     * @param  DataCollection<int, ProfileLinkData>  $links
     */
    public function __construct(
        #[Required, StringType, Min(2), Max(40)]
        public readonly string $name,
        #[Required, StringType, Rule(new WhatsappNumberRule)]
        public readonly string $whatsapp_number,
        #[DataCollectionOf(SocialLinkData::class), Max(10)]
        public readonly DataCollection $socials,
        #[DataCollectionOf(ProfileLinkData::class)]
        public readonly DataCollection $links,
        #[Required]
        public readonly AddressInputData $address,
        #[Nullable, StringType, Max(30)]
        public readonly ?string $handle = null,
        #[Nullable, StringType, Max(160)]
        public readonly ?string $bio = null,
        #[Nullable, IntegerType, Min(1)]
        public readonly ?int $avatar_image_id = null,
        #[Nullable, IntegerType, Min(1)]
        public readonly ?int $background_image_id = null,
        #[Nullable, StringType, Max(200)]
        public readonly ?string $whatsapp_message = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** "Portfólio" tab, saved as a whole: up to 3 highlights and 4 photos. */
final class UpdatePortfolioSettingsData extends Data
{
    /**
     * @param  DataCollection<int, HighlightData>  $highlights
     * @param  DataCollection<int, PortfolioPhotoInputData>  $photos
     */
    public function __construct(
        #[DataCollectionOf(HighlightData::class), Max(3)]
        public readonly DataCollection $highlights,
        #[DataCollectionOf(PortfolioPhotoInputData::class), Max(4)]
        public readonly DataCollection $photos,
        #[Nullable, IntegerType, Min(1)]
        public readonly ?int $cover_image_id = null,
        #[Nullable, StringType, Max(200)]
        public readonly ?string $quote = null,
        #[Nullable, StringType, Max(1200)]
        public readonly ?string $story = null,
        public readonly bool $show_rating = false,
        public readonly bool $show_reviews = false,
    ) {}
}

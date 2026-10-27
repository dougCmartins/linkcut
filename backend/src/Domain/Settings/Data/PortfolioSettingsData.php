<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class PortfolioSettingsData extends Data
{
    /**
     * @param  DataCollection<int, HighlightData>  $highlights
     * @param  DataCollection<int, PortfolioPhotoData>  $photos
     */
    public function __construct(
        public readonly ?ImageData $cover,
        public readonly string $quote,
        public readonly string $story,
        #[DataCollectionOf(HighlightData::class)]
        public readonly DataCollection $highlights,
        #[DataCollectionOf(PortfolioPhotoData::class)]
        public readonly DataCollection $photos,
        public readonly bool $show_rating,
        public readonly bool $show_reviews,
    ) {}
}

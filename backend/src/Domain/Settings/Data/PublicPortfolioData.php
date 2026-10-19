<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** Portfolio as a visitor sees it: only highlights with a value. */
final class PublicPortfolioData extends Data
{
    /**
     * @param  DataCollection<int, HighlightData>  $highlights
     * @param  DataCollection<int, PublicPhotoData>  $photos
     */
    public function __construct(
        public readonly ?string $cover_url,
        public readonly string $quote,
        public readonly string $story,
        #[DataCollectionOf(HighlightData::class)]
        public readonly DataCollection $highlights,
        #[DataCollectionOf(PublicPhotoData::class)]
        public readonly DataCollection $photos,
    ) {}
}

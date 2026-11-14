<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** Image ids to check or to resolve. An empty list is nothing to do. */
final class ImageIdsData extends Data
{
    /** @param DataCollection<int, ImageIdData> $ids */
    public function __construct(
        #[DataCollectionOf(ImageIdData::class)]
        public readonly DataCollection $ids,
    ) {}
}

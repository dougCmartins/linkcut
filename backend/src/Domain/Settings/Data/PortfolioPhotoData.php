<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

final class PortfolioPhotoData extends Data
{
    public function __construct(
        public readonly int $image_id,
        public readonly string $url,
        public readonly string $alt,
    ) {}
}

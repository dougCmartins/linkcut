<?php

declare(strict_types=1);

namespace Domain\Catalog\Data;

use Spatie\LaravelData\Data;

/** One active product as the client area shows it. */
final class PublicProductData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $price_in_cents,
        public readonly ?string $description,
        public readonly ?string $image_url,
    ) {}
}

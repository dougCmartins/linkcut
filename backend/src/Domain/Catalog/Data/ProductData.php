<?php

declare(strict_types=1);

namespace Domain\Catalog\Data;

use Spatie\LaravelData\Data;

/** A stored product. The photo url is resolved outside this domain. */
final class ProductData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $price_in_cents,
        public readonly ?string $description,
        public readonly ?int $image_id,
        public readonly bool $is_active,
        public readonly int $position,
    ) {}
}

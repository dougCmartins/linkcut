<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Catalog\Data;

use Domain\Settings\Data\ImageData;
use Spatie\LaravelData\Data;

/** A product as the panel reads it: the photo is the uploaded image, or null. */
final class CatalogProductData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $price_in_cents,
        public readonly ?string $description,
        public readonly ?ImageData $image,
        public readonly bool $is_active,
        public readonly int $position,
    ) {}
}

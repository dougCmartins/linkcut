<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

/** An uploaded image: the id goes back in the settings, the url is what the pages show. */
final class ImageData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $url,
    ) {}
}

<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

final class PublicPhotoData extends Data
{
    public function __construct(
        public readonly string $url,
        public readonly string $alt,
    ) {}
}

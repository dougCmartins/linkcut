<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

/** What WhatsApp, Instagram and Facebook show when a barbershop's link is shared (Open Graph). */
final class ShareCardData extends Data
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        /** Absolute URL (crawlers do not resolve "/storage/..."); null = no picture. */
        public readonly ?string $image_url,
        /** The page the visitor is sent to. */
        public readonly string $url,
    ) {}
}

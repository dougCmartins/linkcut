<?php

declare(strict_types=1);

namespace Domain\Notification\Data;

use Spatie\LaravelData\Data;

/** What the notification shows. `url` opens on tap; `tag` groups notifications of the same kind. */
final class PushMessageData extends Data
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly string $url,
        public readonly string $tag,
    ) {}
}

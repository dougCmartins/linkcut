<?php

declare(strict_types=1);

namespace Domain\Notification\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StartsWith;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

/** The PushSubscription the browser gives (endpoint of its push service + keys), as JSON. */
final class PushSubscriptionInputData extends Data
{
    public function __construct(
        #[Required, StringType, Url, StartsWith('https://'), Max(500)]
        public readonly string $endpoint,
        #[Required]
        public readonly PushKeysData $keys,
    ) {}
}

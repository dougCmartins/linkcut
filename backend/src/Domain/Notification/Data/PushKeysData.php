<?php

declare(strict_types=1);

namespace Domain\Notification\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Keys the browser created for this subscription (base64url). */
final class PushKeysData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $p256dh,
        #[Required, StringType, Max(255)]
        public readonly string $auth,
    ) {}
}

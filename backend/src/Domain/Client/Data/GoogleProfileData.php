<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Data;

/** The Google account, already read off Socialite. Not the HTTP request. */
final class GoogleProfileData extends Data
{
    public function __construct(
        public readonly string $google_id,
        public readonly string $email,
        public readonly string $name,
        public readonly bool $email_verified,
    ) {}
}

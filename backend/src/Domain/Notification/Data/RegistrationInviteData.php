<?php

declare(strict_types=1);

namespace Domain\Notification\Data;

use Spatie\LaravelData\Data;

/** The single "complete your sign-up" e-mail. */
final class RegistrationInviteData extends Data
{
    public function __construct(
        public readonly string $client_name,
        public readonly string $client_email,
        public readonly string $registration_url,
        public readonly string $opt_out_url,
    ) {}
}

<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Data;

final class ClientData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
        /** Completed the sign-up (has a password). */
        public readonly bool $has_account,
        /** Gets the reminders and invitations by e-mail (false after the opt-out). */
        public readonly bool $emails_enabled = true,
        /** The account is linked to Google. The Google id itself stays off the API. */
        public readonly bool $google_linked = false,
    ) {}
}

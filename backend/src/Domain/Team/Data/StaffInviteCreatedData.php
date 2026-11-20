<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

final class StaffInviteCreatedData extends Data
{
    public function __construct(
        public readonly StaffData $staff,
        public readonly string $email,
        /** Plain token — only for sending the e-mail; never persist or return on APIs. */
        public readonly string $plain_token,
        public readonly string $invite_url,
    ) {}
}

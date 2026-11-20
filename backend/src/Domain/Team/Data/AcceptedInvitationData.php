<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** An invitation just used: the barber, their unit and the e-mail the link was sent to. */
final class AcceptedInvitationData extends Data
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $staff_id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}

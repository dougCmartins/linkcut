<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Data;

/** The token from the invitation link (only its hash is stored). */
final class InvitationTokenData extends Data
{
    public function __construct(public readonly string $token) {}
}

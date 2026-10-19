<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Spatie\LaravelData\Data;

/** Opens a session for a user. A refresh keeps the family of the token it rotates. */
final class IssueSessionData extends Data
{
    public function __construct(
        public readonly int $user_id,
        public readonly ?string $family_id = null,
    ) {}
}

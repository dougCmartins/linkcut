<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Spatie\LaravelData\Data;

final class UserIdData extends Data
{
    public function __construct(public readonly int $user_id) {}
}

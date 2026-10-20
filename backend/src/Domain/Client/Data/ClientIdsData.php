<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Data;

final class ClientIdsData extends Data
{
    /** @param list<int> $ids */
    public function __construct(public readonly array $ids) {}
}

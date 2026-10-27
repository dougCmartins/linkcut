<?php

declare(strict_types=1);

namespace Domain\Review\Data;

use Spatie\LaravelData\Data;

final class ClientReviewsQueryData extends Data
{
    public function __construct(public readonly int $client_id) {}
}

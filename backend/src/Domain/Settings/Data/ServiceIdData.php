<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

final class ServiceIdData extends Data
{
    public function __construct(public readonly int $service_id) {}
}

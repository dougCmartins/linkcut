<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Data;

/** Reminders and invitations by e-mail on or off (replies to the client's own requests always go). */
final class ClientEmailPreferenceData extends Data
{
    public function __construct(
        #[Present, BooleanType]
        public readonly bool $enabled,
    ) {}
}

<?php

declare(strict_types=1);

namespace Domain\Client\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** "Minha conta": the client's name and WhatsApp (the e-mail is the login and does not change here). */
final class ClientProfileInputData extends Data
{
    public function __construct(
        #[Required, StringType, Min(2), Max(60)]
        public readonly string $name,
        /** As typed, e.g. "(11) 91234-5678": stored as digits only. */
        #[Required, StringType, Regex('/^[\d\s()+.-]{10,20}$/')]
        public readonly string $phone,
    ) {}
}

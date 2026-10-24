<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BarbershopAccount\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** "Excluir barbearia": the password and the page link typed again, so nobody deletes it by accident. */
final class DeleteBarbershopData extends Data
{
    public function __construct(
        #[Required, StringType, Max(72)]
        public readonly string $password,
        #[Required, StringType, Max(60)]
        public readonly string $slug_confirmation,
    ) {}
}

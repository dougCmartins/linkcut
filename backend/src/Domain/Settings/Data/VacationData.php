<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\References\FieldReference;

/**
 * Vacation lock. Missing start = from now on; missing end = until turned off.
 * The field reference is explicit because it lives inside the schedule form.
 */
final class VacationData extends Data
{
    public function __construct(
        #[Required, BooleanType]
        public readonly bool $is_enabled,
        #[Nullable, DateFormat('Y-m-d')]
        public readonly ?string $starts_on = null,
        #[Nullable, DateFormat('Y-m-d'), AfterOrEqual(new FieldReference('starts_on'))]
        public readonly ?string $ends_on = null,
        #[Nullable, StringType, Max(160)]
        public readonly ?string $message = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Domain\Team\Enums\StaffTimeBlockRecurrence;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class CreateStaffTimeBlockData extends Data
{
    /**
     * @param  list<int>|null  $weekdays
     */
    public function __construct(
        #[Required, DateFormat('Y-m-d H:i')]
        public readonly string $starts_at,
        #[Required, DateFormat('Y-m-d H:i')]
        public readonly string $ends_at,
        #[Nullable, Enum(StaffTimeBlockRecurrence::class)]
        public readonly ?StaffTimeBlockRecurrence $recurrence = null,
        #[Nullable, ArrayType]
        public readonly ?array $weekdays = null,
        #[Nullable, StringType, Max(200)]
        public readonly ?string $note = null,
    ) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'weekdays.*' => ['integer', 'between:0,6', 'distinct'],
            'ends_at' => ['after:starts_at'],
        ];
    }
}

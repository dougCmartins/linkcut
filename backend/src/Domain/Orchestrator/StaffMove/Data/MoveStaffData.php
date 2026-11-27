<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Data;

use Domain\Team\Data\StaffBusinessHourData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * "Mover para outra filial" (docs/decisoes/0003): the destination unit, the kind, and the barber's hours, services and
 * switches there. The owner fills hours and services in the move itself (each unit has its own).
 */
final class MoveStaffData extends Data
{
    /**
     * @param  DataCollection<int, StaffBusinessHourData>  $weekly_hours
     * @param  list<int>  $service_ids
     */
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public readonly int $to_unit_id,
        /** "transfer" (no end) or "loan" (until `ends_on`, then the barber comes back). */
        #[Required, In(['transfer', 'loan'])]
        public readonly string $kind,
        #[Required, DataCollectionOf(StaffBusinessHourData::class)]
        public readonly DataCollection $weekly_hours,
        #[Required, ArrayType]
        public readonly array $service_ids,
        #[Required, In(['keep', 'reset'])]
        public readonly string $permissions,
        /** The loan's last day at the destination (required for a loan, ignored for a transfer). */
        #[Nullable, Date, DateFormat('Y-m-d')]
        public readonly ?string $ends_on = null,
    ) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return ['service_ids.*' => ['integer', 'min:1', 'distinct']];
    }
}

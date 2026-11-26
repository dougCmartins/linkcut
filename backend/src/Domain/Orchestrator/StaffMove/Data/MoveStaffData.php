<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Data;

use Domain\Team\Data\StaffBusinessHourData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
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
        /** Only "transfer" for now; the loan comes with step P2. */
        #[Required, In(['transfer'])]
        public readonly string $kind,
        #[Required, DataCollectionOf(StaffBusinessHourData::class)]
        public readonly DataCollection $weekly_hours,
        #[Required, ArrayType]
        public readonly array $service_ids,
        #[Required, In(['keep', 'reset'])]
        public readonly string $permissions,
    ) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return ['service_ids.*' => ['integer', 'min:1', 'distinct']];
    }
}

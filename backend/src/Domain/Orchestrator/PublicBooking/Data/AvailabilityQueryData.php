<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Data;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

/** GET /public/{slug}/availability?service_ids[]=… — without services, the shortest one is used. */
final class AvailabilityQueryData extends Data
{
    public function __construct(
        /** @var list<int>|null */
        #[Nullable, ArrayType, Max(5)]
        public readonly ?array $service_ids = null,
    ) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return ['service_ids.*' => ['integer', 'min:1', 'distinct']];
    }
}

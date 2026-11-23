<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class UpdateStaffServicesData extends Data
{
    /**
     * @param  list<int>  $service_ids
     */
    public function __construct(
        #[Required, ArrayType]
        public readonly array $service_ids,
    ) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return ['service_ids.*' => ['integer', 'min:1', 'distinct']];
    }
}

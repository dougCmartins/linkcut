<?php

declare(strict_types=1);

namespace Domain\Catalog\Data;

use Domain\Catalog\Support\PositiveIntegerList;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;

/** The full order of this barbershop's products. The action checks it matches the stored ids. */
final class ProductOrderData extends Data
{
    /** @param list<int> $ids */
    public function __construct(
        #[Present, ArrayType, Rule(new PositiveIntegerList)]
        public readonly array $ids,
    ) {}
}

<?php

declare(strict_types=1);

namespace Domain\Catalog\Support;

/** A technical cap so the public page does not load an unbounded gallery of photos. Not a billing plan. */
final class ProductLimits
{
    public const PRODUCTS_LIMIT = 50;
}

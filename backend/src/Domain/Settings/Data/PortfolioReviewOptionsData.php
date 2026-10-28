<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

/** Whether visitors see the average under the photo and the comments on the portfolio. */
final class PortfolioReviewOptionsData extends Data
{
    public function __construct(
        public readonly bool $show_rating,
        public readonly bool $show_reviews,
    ) {}
}

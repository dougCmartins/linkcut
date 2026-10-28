<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\PortfolioReviewOptionsData;
use Domain\Settings\Models\PortfolioSettings;

/** The two switches that decide what visitors see. Defaults to off when the row is missing. */
final class ShowPortfolioReviewOptions
{
    public function handle(): PortfolioReviewOptionsData
    {
        $portfolio = PortfolioSettings::query()->first(['show_rating', 'show_reviews']);

        return new PortfolioReviewOptionsData(
            show_rating: (bool) ($portfolio->show_rating ?? false),
            show_reviews: (bool) ($portfolio->show_reviews ?? false),
        );
    }
}

<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

/** The public pages (bio link and portfolio) of a barbershop, with its theme. */
final class PublicPageData extends Data
{
    public function __construct(
        public readonly PublicProfileData $profile,
        public readonly PublicPortfolioData $portfolio,
        public readonly ThemeSettingsData $theme,
    ) {}
}

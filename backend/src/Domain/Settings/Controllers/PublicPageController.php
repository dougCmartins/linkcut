<?php

declare(strict_types=1);

namespace Domain\Settings\Controllers;

use Domain\Settings\Actions\ShowBookingOptions;
use Domain\Settings\Actions\ShowPublicPage;
use Domain\Settings\Data\BookingOptionsData;
use Domain\Settings\Data\PublicPageData;

/** Public endpoints. The tenant comes from the {slug} (middleware tenant.slug). */
final class PublicPageController
{
    public function show(ShowPublicPage $action): PublicPageData
    {
        return $action->handle();
    }

    public function bookingOptions(ShowBookingOptions $action): BookingOptionsData
    {
        return $action->handle();
    }
}

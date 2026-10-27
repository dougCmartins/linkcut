<?php

declare(strict_types=1);

namespace Domain\Settings\Controllers;

use Domain\Settings\Actions\ShowBookingOptions;
use Domain\Settings\Data\BookingOptionsData;

/** Public booking options. The page itself is assembled by the public-page orchestrator. */
final class PublicPageController
{
    public function bookingOptions(ShowBookingOptions $action): BookingOptionsData
    {
        return $action->handle();
    }
}

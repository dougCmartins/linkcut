<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\ServiceData;
use Domain\Settings\Data\ServiceIdData;
use Domain\Settings\Models\Service;

/**
 * Current price and cost of a service, also of a removed one (an old booking can still be concluded).
 * Used when an appointment is concluded.
 */
final class ShowService
{
    public function handle(ServiceIdData $data): ServiceData
    {
        return ServiceData::from(Service::query()->withTrashed()->findOrFail($data->service_id));
    }
}

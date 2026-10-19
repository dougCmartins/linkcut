<?php

declare(strict_types=1);

namespace Domain\Identity\Controllers;

use Domain\Identity\Actions\SendTwoFactorCode;
use Domain\Identity\Data\CodeDeliveryData;
use Domain\Identity\Data\SendTwoFactorCodeData;

final class TwoFactorController
{
    public function send(SendTwoFactorCodeData $data, SendTwoFactorCode $action): CodeDeliveryData
    {
        return $action->handle($data);
    }
}

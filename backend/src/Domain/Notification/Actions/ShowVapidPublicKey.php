<?php

declare(strict_types=1);

namespace Domain\Notification\Actions;

use Domain\Notification\Data\VapidPublicKeyData;

final class ShowVapidPublicKey
{
    public function handle(): VapidPublicKeyData
    {
        return new VapidPublicKeyData(public_key: (string) config('services.webpush.public_key'));
    }
}

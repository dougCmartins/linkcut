<?php

declare(strict_types=1);

namespace Domain\Notification\Controllers;

use Domain\Notification\Actions\DeletePushSubscription;
use Domain\Notification\Actions\SavePushSubscription;
use Domain\Notification\Actions\ShowVapidPublicKey;
use Domain\Notification\Data\PushEndpointData;
use Domain\Notification\Data\PushSubscriptionInputData;
use Domain\Notification\Data\VapidPublicKeyData;
use Illuminate\Http\Response;

final class PushController
{
    public function publicKey(ShowVapidPublicKey $action): VapidPublicKeyData
    {
        return $action->handle();
    }

    public function subscribe(PushSubscriptionInputData $data, SavePushSubscription $action): Response
    {
        $action->handle($data);

        return response()->noContent();
    }

    public function unsubscribe(PushEndpointData $data, DeletePushSubscription $action): Response
    {
        $action->handle($data);

        return response()->noContent();
    }
}

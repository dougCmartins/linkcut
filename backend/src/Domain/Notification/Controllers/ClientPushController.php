<?php

declare(strict_types=1);

namespace Domain\Notification\Controllers;

use Domain\Notification\Actions\SaveClientPushSubscription;
use Domain\Notification\Actions\ShowVapidPublicKey;
use Domain\Notification\Data\ClientPushSubscriptionData;
use Domain\Notification\Data\PushSubscriptionInputData;
use Domain\Notification\Data\VapidPublicKeyData;
use Illuminate\Http\Response;

/** Pushes for clients: from "Minha conta" (session) or right after booking (signed link of that client). */
final class ClientPushController
{
    public function publicKey(ShowVapidPublicKey $action): VapidPublicKeyData
    {
        return $action->handle();
    }

    public function subscribeSignedIn(PushSubscriptionInputData $data, SaveClientPushSubscription $action): Response
    {
        $action->handle(new ClientPushSubscriptionData(client_id: (int) auth('client')->id(), subscription: $data));

        return response()->noContent();
    }

    /** The link came with the booking confirmation and is valid for a short time (signed route). */
    public function subscribeAfterBooking(int $client, PushSubscriptionInputData $data, SaveClientPushSubscription $action): Response
    {
        $action->handle(new ClientPushSubscriptionData(client_id: $client, subscription: $data));

        return response()->noContent();
    }
}

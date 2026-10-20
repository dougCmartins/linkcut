<?php

declare(strict_types=1);

namespace Domain\Client\Controllers;

use Domain\Client\Actions\OptOutClientEmails;
use Domain\Client\Data\ClientIdData;
use Illuminate\Http\RedirectResponse;

/** Opened from the e-mail (signed link, no sign-in): turns reminders off and shows the SPA confirmation page. */
final class ClientEmailController
{
    public function optOut(int $client, OptOutClientEmails $action): RedirectResponse
    {
        $action->handle(new ClientIdData(client_id: $client));

        return redirect()->away(rtrim((string) config('app.frontend_url'), '/').'/conta/lembretes-desativados');
    }
}

<?php

declare(strict_types=1);

namespace Domain\Client\Support;

use Illuminate\Support\Facades\URL;

/** Links that go in the e-mails to the client. */
final class ClientLinks
{
    /** The SPA page that completes the sign-up, with the e-mail filled in. */
    public static function registration(string $email): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/conta/finalizar?email='.rawurlencode($email);
    }

    /** Signed API link (no sign-in needed): stops reminders and invitations for this client. */
    public static function optOut(int $clientId): string
    {
        return URL::signedRoute('client.emails.opt-out', ['client' => $clientId]);
    }
}

<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientEmails\Actions;

use Domain\Client\Actions\ListClientsToInvite;
use Domain\Client\Actions\MarkClientInvited;
use Domain\Client\Data\ClientIdData;
use Domain\Client\Data\ClientInviteData;
use Domain\Notification\Actions\SendRegistrationInvite;
use Domain\Notification\Data\RegistrationInviteData;
use Illuminate\Support\Facades\Log;

/** Hourly: one "complete your sign-up" e-mail to each client who booked and didn't sign up (never twice). */
final class InviteClientsToRegister
{
    public function __construct(
        private readonly ListClientsToInvite $listClients,
        private readonly SendRegistrationInvite $sendInvite,
        private readonly MarkClientInvited $markInvited,
    ) {}

    /** @return int how many invitations were sent */
    public function handle(): int
    {
        $sent = 0;
        foreach ($this->listClients->handle() as $client) {
            /** @var ClientInviteData $client */
            $this->sendInvite->handle(new RegistrationInviteData(
                client_name: $client->name,
                client_email: $client->email,
                registration_url: $client->registration_url,
                opt_out_url: $client->opt_out_url,
            ));
            $this->markInvited->handle(new ClientIdData(client_id: $client->id));
            $sent++;
        }
        Log::info('client_emails_invitations_sent', ['count' => $sent]);

        return $sent;
    }
}

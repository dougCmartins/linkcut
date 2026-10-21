<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientEmails\Actions;

use Domain\Client\Actions\ListClientsToInvite;
use Domain\Client\Actions\MarkClientsInvited;
use Domain\Client\Data\ClientIdsData;
use Domain\Client\Data\ClientInviteBatchData;
use Domain\Client\Data\ClientInviteData;
use Domain\Notification\Actions\SendRegistrationInvite;
use Domain\Notification\Data\RegistrationInviteData;
use Illuminate\Support\Facades\Log;

/**
 * Hourly: one "complete your sign-up" e-mail to each client who booked and didn't sign up (never twice).
 * Goes in rounds: each round reads a batch and marks it with one UPDATE, so the next round gets the next clients.
 */
final class InviteClientsToRegister
{
    public function __construct(
        private readonly ListClientsToInvite $listClients,
        private readonly SendRegistrationInvite $sendInvite,
        private readonly MarkClientsInvited $markInvited,
    ) {}

    /** @return int how many invitations were sent */
    public function handle(): int
    {
        $batch = new ClientInviteBatchData;
        $sent = 0;
        do {
            $clients = $this->listClients->handle($batch)->all();
            $invited = [];
            try {
                foreach ($clients as $client) {
                    /** @var ClientInviteData $client */
                    $this->sendInvite->handle(new RegistrationInviteData(
                        client_name: $client->name,
                        client_email: $client->email,
                        registration_url: $client->registration_url,
                        opt_out_url: $client->opt_out_url,
                    ));
                    $invited[] = $client->id;
                }
            } finally {
                // Also after a failure: who was already invited is never invited twice.
                $this->markInvited->handle(new ClientIdsData(ids: $invited));
            }
            $sent += count($invited);
        } while (count($clients) === $batch->limit);
        Log::info('client_emails_invitations_sent', ['count' => $sent]);

        return $sent;
    }
}

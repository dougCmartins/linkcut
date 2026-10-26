<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientInviteBatchData;
use Domain\Client\Data\ClientInviteData;
use Domain\Client\Models\Client;
use Domain\Client\Support\ClientLinks;
use Spatie\LaravelData\DataCollection;

/** Clients who booked a while ago, didn't complete the sign-up, were never invited and didn't opt out. */
final class ListClientsToInvite
{
    /** @return DataCollection<int, ClientInviteData> */
    public function handle(ClientInviteBatchData $batch): DataCollection
    {
        $clients = Client::query()
            ->whereNull('password')
            ->whereNull('google_id')
            ->whereNull('registration_invited_at')
            ->whereNull('emails_opted_out_at')
            ->where('created_at', '<=', now()->subHours((int) config('client.invite_after_hours')))
            ->orderBy('id')
            ->limit($batch->limit)
            ->get(['id', 'name', 'email'])
            ->map(fn (Client $client): ClientInviteData => new ClientInviteData(
                id: $client->id,
                name: $client->name,
                email: $client->email,
                registration_url: ClientLinks::registration($client->email),
                opt_out_url: ClientLinks::optOut($client->id),
            ));

        return ClientInviteData::collect($clients, DataCollection::class);
    }
}

<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientIdData;
use Domain\Client\Models\Client;
use Illuminate\Support\Facades\Log;

/** "Não quero receber lembretes": no more reminders or invitations (codes and answers still go). */
final class OptOutClientEmails
{
    public function handle(ClientIdData $data): void
    {
        Client::query()->whereKey($data->client_id)->whereNull('emails_opted_out_at')->update(['emails_opted_out_at' => now()]);
        Log::info('client_emails_opted_out', ['client_id' => $data->client_id]);
    }
}

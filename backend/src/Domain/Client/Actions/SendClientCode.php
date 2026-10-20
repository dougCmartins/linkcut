<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientCodeRequestData;
use Domain\Client\Data\CodeSentData;
use Domain\Client\Enums\CodePurpose;
use Domain\Client\Models\Client;
use Domain\Client\Models\ClientEmailCode;
use Domain\Client\Notifications\ClientCodeNotification;
use Domain\Client\Support\ClientCode;
use Illuminate\Support\Facades\Log;

/**
 * Sends a code when it makes sense: sign-up only for a client without password (who booked), new password only
 * for a client with one. The answer is always the same, so nobody learns which e-mails have an account.
 */
final class SendClientCode
{
    public function handle(ClientCodeRequestData $data): CodeSentData
    {
        $ttlMinutes = (int) config('client.code.ttl_minutes');
        $resendAfter = (int) config('client.code.resend_after_seconds');
        $client = Client::query()->where('email', mb_strtolower(trim($data->email)))->first();
        $eligible = $client && ($data->purpose === CodePurpose::Registration ? ! $client->hasAccount() : $client->hasAccount());

        if ($eligible && ! $this->sentRecently($client, $data->purpose, $resendAfter)) {
            $code = ClientCode::generate();
            ClientEmailCode::query()->updateOrCreate(
                ['client_id' => $client->id, 'purpose' => $data->purpose],
                [
                    'code_hash' => ClientCode::hash($client->id, $data->purpose, $code),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes($ttlMinutes),
                    'sent_at' => now(),
                ],
            );
            $client->notify(new ClientCodeNotification($code, $data->purpose));
            Log::info('client_code_sent', ['client_id' => $client->id, 'purpose' => $data->purpose->value]);
        }

        return new CodeSentData(expires_in: $ttlMinutes * 60, resend_after: $resendAfter);
    }

    private function sentRecently(Client $client, CodePurpose $purpose, int $seconds): bool
    {
        return ClientEmailCode::query()
            ->where('client_id', $client->id)
            ->where('purpose', $purpose)
            ->where('sent_at', '>', now()->subSeconds($seconds))
            ->exists();
    }
}

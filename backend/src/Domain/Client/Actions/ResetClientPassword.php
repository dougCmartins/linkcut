<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientIdData;
use Domain\Client\Data\ClientPasswordData;
use Domain\Client\Data\ClientSessionData;
use Domain\Client\Enums\CodePurpose;
use Domain\Client\Models\ClientRefreshToken;
use Illuminate\Support\Facades\Log;

/** "Esqueci minha senha": new password with the code; other sessions are ended. */
final class ResetClientPassword
{
    public function __construct(
        private readonly ConsumeClientCode $consumeCode,
        private readonly IssueClientSession $issueSession,
    ) {}

    public function handle(ClientPasswordData $data): ClientSessionData
    {
        $client = $this->consumeCode->handle($data->email, CodePurpose::Password, $data->code);
        $client->update(['password' => $data->password]);
        ClientRefreshToken::query()->where('client_id', $client->id)->active()->update(['revoked_at' => now()]);
        $client->tokens()->update(['revoked' => true]);
        Log::info('client_password_reset', ['client_id' => $client->id]);

        return $this->issueSession->handle(new ClientIdData(client_id: $client->id));
    }
}

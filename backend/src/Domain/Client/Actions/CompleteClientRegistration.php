<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientIdData;
use Domain\Client\Data\ClientPasswordData;
use Domain\Client\Data\ClientSessionData;
use Domain\Client\Enums\CodePurpose;
use Illuminate\Support\Facades\Log;

/** The code proves the e-mail is the client's; then the password is created and the client is signed in. */
final class CompleteClientRegistration
{
    public function __construct(
        private readonly ConsumeClientCode $consumeCode,
        private readonly IssueClientSession $issueSession,
    ) {}

    public function handle(ClientPasswordData $data): ClientSessionData
    {
        $client = $this->consumeCode->handle($data->email, CodePurpose::Registration, $data->code);
        $client->update(['password' => $data->password, 'email_verified_at' => now()]);
        Log::info('client_registered', ['client_id' => $client->id]);

        return $this->issueSession->handle(new ClientIdData(client_id: $client->id));
    }
}

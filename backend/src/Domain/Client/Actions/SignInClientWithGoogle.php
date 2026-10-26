<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\ClientContactData;
use Domain\Client\Data\ClientIdData;
use Domain\Client\Data\GoogleProfileData;
use Domain\Client\Exceptions\ClientGoogleSignInRejectedException;
use Domain\Client\Models\Client;
use Illuminate\Support\Facades\Log;

/**
 * Finds the client by Google id, or by e-mail, or creates one (name and e-mail, no password).
 * Does not open a session: the one-time code does that, so an abandoned return leaves no token.
 */
final class SignInClientWithGoogle
{
    public function __construct(private readonly FindOrCreateClient $findOrCreateClient) {}

    public function handle(GoogleProfileData $data): ClientIdData
    {
        $email = mb_strtolower(trim($data->email));
        if (! $data->email_verified || $data->google_id === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            Log::info('client_google_sign_in_rejected');

            throw new ClientGoogleSignInRejectedException;
        }

        $byGoogle = Client::query()->where('google_id', $data->google_id)->first();
        if ($byGoogle) {
            $this->markVerified($byGoogle);
            Log::info('client_google_sign_in', ['client_id' => $byGoogle->id]);

            return new ClientIdData(client_id: $byGoogle->id);
        }

        $found = $this->findOrCreateClient->handle(new ClientContactData(
            name: $this->displayName($data->name, $email),
            email: $email,
            phone: '',
        ));
        $client = Client::query()->findOrFail($found->id);
        if (! empty($client->google_id) && $client->google_id !== $data->google_id) {
            Log::info('client_google_sign_in_rejected', ['client_id' => $client->id]);

            throw new ClientGoogleSignInRejectedException;
        }

        $client->google_id = $data->google_id;
        $this->markVerified($client);
        Log::info('client_google_sign_in', ['client_id' => $client->id]);

        return new ClientIdData(client_id: $client->id);
    }

    /** Google already proved the e-mail. A client created from a booking may not have this yet. */
    private function markVerified(Client $client): void
    {
        if (! $client->email_verified_at) {
            $client->email_verified_at = now();
        }
        $client->save();
    }

    private function displayName(string $name, string $email): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            $local = strstr($email, '@', true);
            $trimmed = $local !== false && $local !== '' ? $local : 'Client';
        }

        return mb_substr($trimmed, 0, 60);
    }
}

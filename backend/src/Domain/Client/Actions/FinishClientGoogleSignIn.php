<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\GoogleCallbackData;
use Domain\Client\Data\GoogleProfileData;
use Domain\Client\Exceptions\ClientGoogleSignInRejectedException;
use Domain\Client\Support\ClientGoogleLogin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/** Google's return: checks the state, links the account, and redirects with a one-time code (never a token). */
final class FinishClientGoogleSignIn
{
    private const DENIED = '/conta/entrar?erro=google';

    public function __construct(private readonly SignInClientWithGoogle $signIn) {}

    public function handle(GoogleCallbackData $data): string
    {
        if ($data->error || ! $data->code || ! $data->state) {
            $this->forgetState($data->state);

            return self::DENIED;
        }

        $started = Cache::pull(ClientGoogleLogin::stateKey($data->state));
        if (! is_array($started) || ! is_string($started['volta'] ?? null) || ! is_string($started['nonce'] ?? null)) {
            return self::DENIED;
        }

        try {
            $profile = $this->profile(Socialite::driver('google')->stateless()->user());
            $client = $this->signIn->handle($profile);
        } catch (ClientGoogleSignInRejectedException) {
            return self::DENIED;
        } catch (Throwable $exception) {
            Log::warning('client_google_callback_failed', ['exception' => $exception::class]);

            return self::DENIED;
        }

        $code = Str::random(64);
        Cache::put(
            ClientGoogleLogin::codeKey($code),
            ['client_id' => $client->client_id, 'nonce' => $started['nonce']],
            ClientGoogleLogin::CODE_TTL_SECONDS,
        );

        return '/conta/entrar/google?'.http_build_query([
            'codigo' => $code,
            'volta' => $started['volta'],
        ]);
    }

    private function forgetState(?string $state): void
    {
        if ($state) {
            Cache::pull(ClientGoogleLogin::stateKey($state));
        }
    }

    private function profile(SocialiteUser $user): GoogleProfileData
    {
        $raw = method_exists($user, 'getRaw') ? $user->getRaw() : [];
        if (! is_array($raw)) {
            $raw = [];
        }
        $verified = $raw['email_verified'] ?? $raw['verified_email'] ?? false;

        return new GoogleProfileData(
            google_id: (string) $user->getId(),
            email: (string) $user->getEmail(),
            name: (string) ($user->getName() ?? ''),
            email_verified: $verified === true || $verified === 1 || $verified === 'true',
        );
    }
}

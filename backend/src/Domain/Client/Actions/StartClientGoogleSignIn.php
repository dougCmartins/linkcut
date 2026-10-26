<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Data\GoogleRedirectData;
use Domain\Client\Support\ClientGoogleLogin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/** Sends the browser to Google. The return path waits in the cache, not in a session cookie. */
final class StartClientGoogleSignIn
{
    public function handle(GoogleRedirectData $data): string
    {
        $state = Str::random(40);
        Cache::put(
            ClientGoogleLogin::stateKey($state),
            ClientGoogleLogin::safeReturnPath($data->volta),
            ClientGoogleLogin::STATE_TTL_SECONDS,
        );

        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $state])
            ->redirect()
            ->getTargetUrl();
    }
}

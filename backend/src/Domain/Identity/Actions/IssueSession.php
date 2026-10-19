<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\IssueSessionData;
use Domain\Identity\Data\SessionData;
use Domain\Identity\Models\RefreshToken;
use Domain\Identity\Models\User;
use Domain\Identity\Support\UserDataMapper;
use Illuminate\Support\Str;

/** Creates a short access token (Passport) and an opaque refresh token for a user. */
final class IssueSession
{
    public function handle(IssueSessionData $data): SessionData
    {
        $user = User::query()->findOrFail($data->user_id);
        $accessToken = $user->createToken('dashboard');

        $refreshToken = Str::random(64);
        $refreshTtlMinutes = (int) config('identity.session.refresh_token_ttl_minutes');
        RefreshToken::query()->create([
            'user_id' => $user->id,
            'family_id' => $data->family_id ?: (string) Str::uuid(),
            'token_hash' => hash('sha256', $refreshToken),
            'expires_at' => now()->addMinutes($refreshTtlMinutes),
        ]);

        return new SessionData(
            access_token: (string) $accessToken->accessToken,
            token_type: 'Bearer',
            expires_in: (int) config('identity.session.access_token_ttl_minutes') * 60,
            refresh_token: $refreshToken,
            refresh_expires_in: $refreshTtlMinutes * 60,
            user: UserDataMapper::toData($user),
        );
    }
}

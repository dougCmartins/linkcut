<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\LoginChallengeData;
use Domain\Identity\Data\LoginData;
use Domain\Identity\Data\TwoFactorDestinationData;
use Domain\Identity\Enums\TwoFactorChannel;
use Domain\Identity\Exceptions\InvalidCredentialsException;
use Domain\Identity\Models\TwoFactorChallenge;
use Domain\Identity\Models\User;
use Domain\Identity\Support\ContactMask;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\LaravelData\DataCollection;

/** Step 1 of sign-in: checks the password and opens a 2FA challenge (no session yet). */
final class StartLogin
{
    /** Compared when the e-mail does not exist, so both failures take the same time. */
    private static ?string $dummyHash = null;

    public function handle(LoginData $data): LoginChallengeData
    {
        $user = User::query()->where('email', mb_strtolower($data->email))->first();
        $passwordMatches = Hash::check($data->password, $user?->password ?? self::dummyHash());

        if (! $user || ! $passwordMatches) {
            Log::channel('security')->warning('login_failed', ['email' => ContactMask::email($data->email)]);
            throw new InvalidCredentialsException;
        }

        $challenge = TwoFactorChallenge::query()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes((int) config('identity.two_factor.challenge_ttl_minutes')),
            'attempts' => 0,
            'sends' => 0,
        ]);

        Log::info('login_password_accepted', ['user_id' => $user->id, 'tenant_id' => $user->tenant_id]);

        return new LoginChallengeData(
            challenge_id: $challenge->id,
            preferred_channel: $user->two_factor_channel,
            destinations: new DataCollection(TwoFactorDestinationData::class, $this->destinations($user)),
        );
    }

    private static function dummyHash(): string
    {
        return self::$dummyHash ??= Hash::make(Str::random(32));
    }

    /** @return list<TwoFactorDestinationData> */
    private function destinations(User $user): array
    {
        $destinations = [new TwoFactorDestinationData(TwoFactorChannel::Email, ContactMask::email($user->email))];
        if ($user->phone) {
            $destinations[] = new TwoFactorDestinationData(TwoFactorChannel::Sms, ContactMask::phone($user->phone));
        }

        return $destinations;
    }
}

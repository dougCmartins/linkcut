<?php

declare(strict_types=1);

namespace Domain\Identity\Tests\Concerns;

use Domain\Identity\Models\User;
use Domain\Identity\Notifications\TwoFactorCodeNotification;
use Domain\Tenant\Models\Tenant;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

/** Helpers for the sign-in steps: password → code sent → code read from the faked notification. */
trait SignsIn
{
    /** The page the barber signs in from: /<slug>/entrar of their own barbershop. */
    protected function slugOf(User $user): string
    {
        return (string) Tenant::query()->whereKey($user->tenant_id)->value('slug');
    }

    protected function startLogin(User $user, string $password = 'password'): string
    {
        return $this->postJson('/api/login', ['email' => $user->email, 'password' => $password, 'slug' => $this->slugOf($user)])
            ->assertCreated()
            ->json('challenge_id');
    }

    protected function sendCode(string $challengeId, string $channel = 'email'): TestResponse
    {
        return $this->postJson('/api/two-factor/send', ['challenge_id' => $challengeId, 'channel' => $channel]);
    }

    /** The code the user received (taken from the SMS text of the last notification). */
    protected function lastCodeSentTo(User $user): string
    {
        $code = '';
        Notification::assertSentTo($user, TwoFactorCodeNotification::class, function (TwoFactorCodeNotification $notification) use ($user, &$code): bool {
            preg_match('/\b(\d{6})\b/', $notification->toSms($user), $matches);
            $code = $matches[1];

            return true;
        });

        return $code;
    }

    /** Full sign-in; returns the session JSON. */
    protected function signIn(User $user): array
    {
        Notification::fake();
        $challengeId = $this->startLogin($user);
        $this->sendCode($challengeId)->assertCreated();

        return $this->postJson('/api/two-factor/verify', ['challenge_id' => $challengeId, 'code' => $this->lastCodeSentTo($user)])
            ->assertCreated()
            ->json();
    }
}

<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\SignUpCodeRequestData;
use Domain\Identity\Data\SignUpCodeSentData;
use Domain\Identity\Models\SignUpCode;
use Domain\Identity\Models\User;
use Domain\Identity\Notifications\AccountExistsNotification;
use Domain\Identity\Notifications\SignUpCodeNotification;
use Domain\Identity\Support\SignUpCodeHash;
use Domain\Identity\Support\TwoFactorCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * First step of the sign-up: a code to the e-mail. The answer is always the same; an e-mail that already has an
 * account gets "you already have an account" instead, so nobody learns from the screen which e-mails exist.
 */
final class SendSignUpCode
{
    public function handle(SignUpCodeRequestData $data): SignUpCodeSentData
    {
        $email = mb_strtolower(trim($data->email));
        $ttlMinutes = (int) config('identity.sign_up.code_ttl_minutes');
        $resendAfter = (int) config('identity.sign_up.resend_after_seconds');
        $stored = SignUpCode::query()->where('email', $email)->first();

        if (! $stored?->sent_at->isAfter(now()->subSeconds($resendAfter))) {
            if (User::query()->where('email', $email)->exists()) {
                SignUpCode::query()->updateOrCreate(['email' => $email], ['code_hash' => '', 'attempts' => 0, 'expires_at' => now(), 'sent_at' => now()]);
                Notification::route('mail', $email)->notify(new AccountExistsNotification);
            } else {
                $code = TwoFactorCode::generate();
                SignUpCode::query()->updateOrCreate(['email' => $email], [
                    'code_hash' => SignUpCodeHash::make($email, $code),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes($ttlMinutes),
                    'sent_at' => now(),
                ]);
                Notification::route('mail', $email)->notify(new SignUpCodeNotification($code));
            }
            Log::info('sign_up_code_requested');
        }

        return new SignUpCodeSentData(expires_in: $ttlMinutes * 60, resend_after: $resendAfter);
    }
}

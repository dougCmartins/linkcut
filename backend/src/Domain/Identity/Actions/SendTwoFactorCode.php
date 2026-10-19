<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\CodeDeliveryData;
use Domain\Identity\Data\SendTwoFactorCodeData;
use Domain\Identity\Enums\TwoFactorChannel;
use Domain\Identity\Exceptions\ChallengeExpiredException;
use Domain\Identity\Exceptions\ChannelUnavailableException;
use Domain\Identity\Exceptions\ResendTooSoonException;
use Domain\Identity\Exceptions\SendLimitReachedException;
use Domain\Identity\Exceptions\TooManyAttemptsException;
use Domain\Identity\Models\TwoFactorChallenge;
use Domain\Identity\Notifications\TwoFactorCodeNotification;
use Domain\Identity\Support\ContactMask;
use Domain\Identity\Support\TwoFactorCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Step 2: sends a new code by e-mail or SMS. A new code replaces the previous one. */
final class SendTwoFactorCode
{
    public function handle(SendTwoFactorCodeData $data): CodeDeliveryData
    {
        [$challenge, $code] = DB::transaction(function () use ($data): array {
            $challenge = TwoFactorChallenge::query()->with('user')->lockForUpdate()->find($data->challenge_id);
            $this->ensureCanSend($challenge, $data->channel);

            $code = TwoFactorCode::generate();
            $challenge->update([
                'channel' => $data->channel,
                'code_hash' => TwoFactorCode::hash($challenge->id, $code),
                'code_expires_at' => now()->addMinutes((int) config('identity.two_factor.code_ttl_minutes')),
                'sends' => $challenge->sends + 1,
                'resend_available_at' => now()->addSeconds((int) config('identity.two_factor.resend_after_seconds')),
            ]);

            return [$challenge, $code];
        });

        $user = $challenge->user;
        $user->notify(new TwoFactorCodeNotification($code, $data->channel, $user->tenant_id));
        Log::info('two_factor_code_sent', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'channel' => $data->channel->value,
        ]);

        return new CodeDeliveryData(
            channel: $data->channel,
            masked_destination: $data->channel === TwoFactorChannel::Sms
                ? ContactMask::phone((string) $user->phone)
                : ContactMask::email($user->email),
            expires_at: $challenge->code_expires_at->toIso8601String(),
            resend_available_at: $challenge->resend_available_at->toIso8601String(),
        );
    }

    private function ensureCanSend(?TwoFactorChallenge $challenge, TwoFactorChannel $channel): void
    {
        if (! $challenge || ! $challenge->isOpen()) {
            throw new ChallengeExpiredException;
        }
        if ($challenge->isLocked()) {
            throw new TooManyAttemptsException;
        }
        if ($challenge->sends >= (int) config('identity.two_factor.max_sends')) {
            throw new SendLimitReachedException;
        }
        if ($challenge->resend_available_at?->isFuture()) {
            throw new ResendTooSoonException;
        }
        if ($channel === TwoFactorChannel::Sms && ! $challenge->user->phone) {
            throw new ChannelUnavailableException;
        }
    }
}

<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\VerifiedUserData;
use Domain\Identity\Data\VerifyTwoFactorCodeData;
use Domain\Identity\Exceptions\ChallengeExpiredException;
use Domain\Identity\Exceptions\CodeExpiredException;
use Domain\Identity\Exceptions\InvalidCodeException;
use Domain\Identity\Exceptions\TooManyAttemptsException;
use Domain\Identity\Models\TwoFactorChallenge;
use Domain\Identity\Support\TwoFactorCode;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Step 3: checks the code. A wrong code counts an attempt (kept even though the request fails);
 * after the maximum the challenge is locked. A right code consumes the challenge.
 */
final class VerifyTwoFactorCode
{
    public function handle(VerifyTwoFactorCodeData $data): VerifiedUserData
    {
        // The attempt counter must be saved before the error is thrown, so the
        // transaction returns the outcome and the exception is raised after commit.
        $outcome = DB::transaction(function () use ($data): VerifiedUserData|DomainException {
            $challenge = TwoFactorChallenge::query()->with('user')->lockForUpdate()->find($data->challenge_id);

            if (! $challenge || ! $challenge->isOpen()) {
                return new ChallengeExpiredException;
            }
            if ($challenge->isLocked()) {
                return new TooManyAttemptsException;
            }
            if (! $challenge->code_hash || $challenge->code_expires_at?->isPast()) {
                return new CodeExpiredException;
            }
            if (! TwoFactorCode::matches($challenge->id, $data->code, $challenge->code_hash)) {
                return $this->registerWrongCode($challenge);
            }

            $challenge->update(['consumed_at' => now(), 'code_hash' => null]);

            return new VerifiedUserData(user_id: $challenge->user->id, tenant_id: $challenge->user->tenant_id);
        });

        if ($outcome instanceof DomainException) {
            throw $outcome;
        }

        return $outcome;
    }

    private function registerWrongCode(TwoFactorChallenge $challenge): DomainException
    {
        $challenge->increment('attempts');
        $context = ['user_id' => $challenge->user_id, 'tenant_id' => $challenge->user->tenant_id];

        if ($challenge->isLocked()) {
            Log::channel('security')->warning('two_factor_challenge_locked', $context);

            return new TooManyAttemptsException;
        }

        Log::channel('security')->notice('two_factor_code_invalid', $context + ['attempts' => $challenge->attempts]);

        return new InvalidCodeException(attemptsLeft: (int) config('identity.two_factor.max_attempts') - $challenge->attempts);
    }
}

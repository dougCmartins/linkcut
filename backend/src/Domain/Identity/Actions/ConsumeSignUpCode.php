<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\SignUpCodeCheckData;
use Domain\Identity\Exceptions\CodeExpiredException;
use Domain\Identity\Exceptions\InvalidCodeException;
use Domain\Identity\Exceptions\TooManyAttemptsException;
use Domain\Identity\Models\SignUpCode;
use Domain\Identity\Support\SignUpCodeHash;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/** Checks the sign-up code (expiry, attempts) and uses it up: a code works only once. */
final class ConsumeSignUpCode
{
    /** Wrong codes also count per e-mail per day: resending does not give new guesses. */
    private const DAY_IN_SECONDS = 86_400;

    public function handle(SignUpCodeCheckData $data): void
    {
        $email = mb_strtolower(trim($data->email));
        $failure = DB::transaction(function () use ($email, $data): ?DomainException {
            $stored = SignUpCode::query()->where('email', $email)->lockForUpdate()->first();
            if (! $stored || $stored->code_hash === '' || $stored->expires_at->isPast()) {
                return new CodeExpiredException;
            }
            $maxAttempts = (int) config('identity.sign_up.max_attempts');
            $dailyKey = 'sign-up-code-failures:'.hash('sha256', $email);
            if ($stored->attempts >= $maxAttempts || RateLimiter::tooManyAttempts($dailyKey, (int) config('identity.sign_up.daily_max_failures'))) {
                return new TooManyAttemptsException;
            }
            if (! hash_equals($stored->code_hash, SignUpCodeHash::make($email, $data->code))) {
                $stored->increment('attempts');
                RateLimiter::hit($dailyKey, self::DAY_IN_SECONDS);
                Log::channel('security')->warning('sign_up_code_wrong');

                return $stored->attempts >= $maxAttempts ? new TooManyAttemptsException : new InvalidCodeException($maxAttempts - $stored->attempts);
            }
            $stored->delete();

            return null;
        });

        // Thrown outside the transaction, so a wrong attempt is still counted.
        if ($failure) {
            throw $failure;
        }
    }
}

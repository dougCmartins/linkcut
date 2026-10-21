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

/** Checks the sign-up code (expiry, attempts) and uses it up: a code works only once. */
final class ConsumeSignUpCode
{
    public function handle(SignUpCodeCheckData $data): void
    {
        $email = mb_strtolower(trim($data->email));
        $failure = DB::transaction(function () use ($email, $data): ?DomainException {
            $stored = SignUpCode::query()->where('email', $email)->lockForUpdate()->first();
            if (! $stored || $stored->code_hash === '' || $stored->expires_at->isPast()) {
                return new CodeExpiredException;
            }
            $maxAttempts = (int) config('identity.sign_up.max_attempts');
            if ($stored->attempts >= $maxAttempts) {
                return new TooManyAttemptsException;
            }
            if (! hash_equals($stored->code_hash, SignUpCodeHash::make($email, $data->code))) {
                $stored->increment('attempts');
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

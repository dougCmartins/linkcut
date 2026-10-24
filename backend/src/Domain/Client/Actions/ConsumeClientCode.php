<?php

declare(strict_types=1);

namespace Domain\Client\Actions;

use Domain\Client\Enums\CodePurpose;
use Domain\Client\Exceptions\ClientCodeExpiredException;
use Domain\Client\Exceptions\ClientInvalidCodeException;
use Domain\Client\Exceptions\ClientTooManyAttemptsException;
use Domain\Client\Models\Client;
use Domain\Client\Models\ClientEmailCode;
use Domain\Client\Support\ClientCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Checks the code (expiry, attempts) and uses it up: a code works only once. Wrong codes also count per account
 * per day, so asking for new codes does not give new guesses (brute force).
 */
final class ConsumeClientCode
{
    private const DAY_IN_SECONDS = 86_400;

    public function handle(string $email, CodePurpose $purpose, string $code): Client
    {
        $outcome = DB::transaction(function () use ($email, $purpose, $code): Client|\DomainException {
            $client = Client::query()->where('email', mb_strtolower(trim($email)))->first();
            $stored = $client
                ? ClientEmailCode::query()->where('client_id', $client->id)->where('purpose', $purpose)->lockForUpdate()->first()
                : null;
            if (! $client || ! $stored || $stored->expires_at->isPast()) {
                return new ClientCodeExpiredException;
            }
            $maxAttempts = (int) config('client.code.max_attempts');
            $dailyKey = "client-code-failures:{$client->id}";
            if ($stored->attempts >= $maxAttempts || RateLimiter::tooManyAttempts($dailyKey, (int) config('client.code.daily_max_failures'))) {
                return new ClientTooManyAttemptsException;
            }
            if (! hash_equals($stored->code_hash, ClientCode::hash($client->id, $purpose, $code))) {
                $stored->increment('attempts');
                RateLimiter::hit($dailyKey, self::DAY_IN_SECONDS);
                Log::channel('security')->warning('client_code_wrong', ['client_id' => $client->id, 'purpose' => $purpose->value]);

                return $stored->attempts >= $maxAttempts
                    ? new ClientTooManyAttemptsException
                    : new ClientInvalidCodeException(attemptsLeft: $maxAttempts - $stored->attempts);
            }
            $stored->delete();

            return $client;
        });

        // Thrown outside the transaction, so a wrong attempt is still counted.
        if ($outcome instanceof \DomainException) {
            throw $outcome;
        }

        return $outcome;
    }
}

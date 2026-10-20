<?php

declare(strict_types=1);

namespace Domain\Client\Exceptions;

use DomainException;

/** Same code as the barbers' sign-in, so the SPA reads both the same way. */
final class ClientInvalidCodeException extends DomainException
{
    public function __construct(public readonly int $attemptsLeft)
    {
        parent::__construct('Invalid code.');
    }

    public function getErrorCode(): string
    {
        return 'INVALID_CODE';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }

    /** @return array{attempts_left: int} */
    public function getErrorData(): array
    {
        return ['attempts_left' => $this->attemptsLeft];
    }
}

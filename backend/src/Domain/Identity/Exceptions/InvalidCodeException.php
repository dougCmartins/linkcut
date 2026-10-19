<?php

declare(strict_types=1);

namespace Domain\Identity\Exceptions;

use DomainException;

/** Wrong 2FA code. Tells how many attempts are left (in the envelope "data"). */
final class InvalidCodeException extends DomainException
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

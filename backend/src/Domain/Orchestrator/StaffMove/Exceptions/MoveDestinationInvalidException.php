<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffMove\Exceptions;

use DomainException;

/** The destination is not another unit of the same brand. */
final class MoveDestinationInvalidException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Pick another unit of the same brand.');
    }

    public function getErrorCode(): string
    {
        return 'MOVE_DESTINATION_INVALID';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}

<?php

declare(strict_types=1);

namespace Domain\Review\Exceptions;

use DomainException;

/** This visit already has a review. The barber cannot reopen it. */
final class ReviewAlreadyExistsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This visit was already reviewed.');
    }

    public function getErrorCode(): string
    {
        return 'REVIEW_ALREADY_EXISTS';
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}

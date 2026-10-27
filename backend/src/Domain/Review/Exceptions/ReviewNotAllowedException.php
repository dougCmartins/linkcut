<?php

declare(strict_types=1);

namespace Domain\Review\Exceptions;

use DomainException;

/** The visit is not completed, so it cannot be reviewed yet. */
final class ReviewNotAllowedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This visit cannot be reviewed.');
    }

    public function getErrorCode(): string
    {
        return 'REVIEW_NOT_ALLOWED';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}

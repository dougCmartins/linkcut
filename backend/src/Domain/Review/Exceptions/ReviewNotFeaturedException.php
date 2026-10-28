<?php

declare(strict_types=1);

namespace Domain\Review\Exceptions;

use DomainException;

/** A hidden review, or one without a comment, cannot be one of the three on the page. */
final class ReviewNotFeaturedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This review cannot be featured.');
    }

    public function getErrorCode(): string
    {
        return 'REVIEW_NOT_FEATURED';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}

<?php

declare(strict_types=1);

namespace Domain\Review\Exceptions;

use DomainException;

/** No review with this id in the current barbershop. */
final class ReviewNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Review not found.');
    }

    public function getErrorCode(): string
    {
        return 'REVIEW_NOT_FOUND';
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}

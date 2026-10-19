<?php

declare(strict_types=1);

namespace Domain\Settings\Exceptions;

use DomainException;

/** The file passed the type check but could not be decoded as an image. */
final class InvalidImageException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The image could not be read.');
    }

    public function getErrorCode(): string
    {
        return 'INVALID_IMAGE';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}

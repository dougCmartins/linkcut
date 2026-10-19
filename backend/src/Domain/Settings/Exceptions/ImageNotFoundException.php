<?php

declare(strict_types=1);

namespace Domain\Settings\Exceptions;

use DomainException;

/** The settings point to an image id that does not exist for this tenant. */
final class ImageNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Image not found. Upload it again.');
    }

    public function getErrorCode(): string
    {
        return 'IMAGE_NOT_FOUND';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}

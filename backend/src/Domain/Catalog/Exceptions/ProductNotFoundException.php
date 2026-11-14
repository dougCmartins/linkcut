<?php

declare(strict_types=1);

namespace Domain\Catalog\Exceptions;

use DomainException;

/** No product with this id in the current barbershop. */
final class ProductNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Product not found.');
    }

    public function getErrorCode(): string
    {
        return 'PRODUCT_NOT_FOUND';
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}

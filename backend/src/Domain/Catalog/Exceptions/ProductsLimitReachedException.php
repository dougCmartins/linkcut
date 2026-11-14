<?php

declare(strict_types=1);

namespace Domain\Catalog\Exceptions;

use DomainException;

/** This barbershop already has the maximum number of products. */
final class ProductsLimitReachedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Product limit reached.');
    }

    public function getErrorCode(): string
    {
        return 'PRODUCTS_LIMIT_REACHED';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}

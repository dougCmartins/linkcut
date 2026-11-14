<?php

declare(strict_types=1);

namespace Domain\Catalog\Exceptions;

use DomainException;

/** The id list is not exactly the products of this barbershop, once each. */
final class ProductOrderInvalidException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Product order is invalid.');
    }

    public function getErrorCode(): string
    {
        return 'PRODUCT_ORDER_INVALID';
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}

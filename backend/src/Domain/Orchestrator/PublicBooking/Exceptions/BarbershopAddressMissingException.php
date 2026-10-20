<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Exceptions;

use DomainException;

/** Nobody books without seeing where the barbershop is: the barber must fill in the address first. */
final class BarbershopAddressMissingException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This barbershop has no address yet.');
    }

    public function getErrorCode(): string
    {
        return 'BARBERSHOP_ADDRESS_MISSING';
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}

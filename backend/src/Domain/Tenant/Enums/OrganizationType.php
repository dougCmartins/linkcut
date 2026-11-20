<?php

declare(strict_types=1);

namespace Domain\Tenant\Enums;

enum OrganizationType: string
{
    /** One barber, one address — same product experience as before organizations. */
    case Individual = 'individual';
    /** Brand with two or more units (main + branches). */
    case MultiUnit = 'multi_unit';
}

<?php

declare(strict_types=1);

namespace Domain\Tenant\Enums;

enum OrganizationType: string
{
    /** A barber on their own: one unit, never sees anything about branches. */
    case Individual = 'individual';
    /** A barbershop with branches: the unit picker shows up in the dashboard. */
    case MultiUnit = 'multi_unit';
}

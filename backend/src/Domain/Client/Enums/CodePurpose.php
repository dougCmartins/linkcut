<?php

declare(strict_types=1);

namespace Domain\Client\Enums;

enum CodePurpose: string
{
    /** Complete the sign-up started by a booking (create the password). */
    case Registration = 'registration';
    /** Forgot the password. */
    case Password = 'password';
}

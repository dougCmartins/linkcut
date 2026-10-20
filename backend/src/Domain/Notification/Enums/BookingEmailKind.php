<?php

declare(strict_types=1);

namespace Domain\Notification\Enums;

/** The e-mails a client gets about a booking (texts in lang/pt_BR/client_mail.php). */
enum BookingEmailKind: string
{
    case Received = 'received';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Reminder = 'reminder';
}

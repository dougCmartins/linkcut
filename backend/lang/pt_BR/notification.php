<?php

declare(strict_types=1);

return [
    // Push sent to the barber when a client requests a time.
    'new_booking' => [
        'title' => 'Novo pedido de agendamento',
        'body' => ':client · :service · :date às :time',
    ],
    // Push sent to the barber when a client cancels from the client area (the time is free again).
    'booking_cancelled' => [
        'title' => 'Horário cancelado pelo cliente',
        'body' => ':client · :service · :date às :time (horário liberado)',
    ],
];

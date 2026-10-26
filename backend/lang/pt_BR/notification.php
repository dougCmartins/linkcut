<?php

declare(strict_types=1);

return [
    // Push sent to the barber when a client requests a time.
    // Pushes to the client (devices that asked for them after booking or in "Minha conta").
    'client_accepted' => [
        'title' => 'Horário confirmado ✅',
        'body' => ':barbershop confirmou :service em :date às :time.',
    ],
    'client_rejected' => [
        'title' => 'Horário não confirmado',
        'body' => ':barbershop não pôde atender :service em :date às :time. Escolha outro horário.',
    ],
    'client_reminder' => [
        'title' => 'Lembrete: amanhã às :time',
        'body' => ':service na :barbershop, :date às :time.',
    ],
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

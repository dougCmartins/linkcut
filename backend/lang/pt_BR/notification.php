<?php

declare(strict_types=1);

return [
    // Push sent to the barber when a client requests a time.
    'new_booking' => [
        'title' => 'Novo pedido de agendamento',
        'body' => ':client · :service · :date às :time',
    ],
];

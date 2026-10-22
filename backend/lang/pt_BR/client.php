<?php

declare(strict_types=1);

return [
    // Shown to the barber in place of the name of a client who deleted the account (LGPD).
    'deleted_name' => 'Cliente removido',
    'code_mail' => [
        'greeting' => 'Olá!',
        'expires' => 'O código vale por :minutes minutos.',
        'ignore' => 'Se não foi você, ignore este e-mail.',
        'registration' => [
            'subject' => 'Seu código para finalizar o cadastro no linkcut',
            'intro' => 'Use este código para criar sua senha e acompanhar seus horários:',
        ],
        'password' => [
            'subject' => 'Seu código para criar uma nova senha no linkcut',
            'intro' => 'Use este código para criar uma nova senha:',
        ],
    ],
];

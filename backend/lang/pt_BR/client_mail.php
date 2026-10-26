<?php

declare(strict_types=1);

// E-mails to the client about bookings (Notification domain).
return [
    'from' => ':barbershop via linkcut',
    'sent_by' => 'Enviado pelo linkcut para :barbershop.',
    'greeting' => 'Olá, :name!',
    // When the name typed at booking has nothing left after removing links and symbols.
    'default_name' => 'cliente',
    'details' => '**:service** · :date às :time · :barbershop',
    'address' => 'Endereço: :address',
    'directions' => 'Como chegar',
    'book_again' => 'Escolher outro horário',
    'register' => 'Finalizar cadastro',
    'register_hint' => 'Finalize seu cadastro para acompanhar seus horários e agendar de novo mais rápido.',
    'whatsapp' => 'Dúvidas? Fale com a barbearia no WhatsApp: :url',
    'opt_out' => 'Não quer mais receber lembretes? [Clique aqui](:url).',
    'received' => [
        'subject' => 'Recebemos seu pedido de horário em :barbershop',
        'intro' => 'Seu pedido foi enviado. Assim que a barbearia responder, avisamos por aqui.',
    ],
    'accepted' => [
        'subject' => 'Horário confirmado: :date às :time em :barbershop',
        'intro' => 'Boa notícia! A barbearia confirmou seu horário.',
    ],
    'rejected' => [
        'subject' => 'Seu pedido em :barbershop não pôde ser confirmado',
        'intro' => 'A barbearia não conseguiu atender neste horário. Que tal escolher outro?',
    ],
    'reminder' => [
        'subject' => 'Lembrete: seu horário :date às :time em :barbershop',
        'intro' => 'Passando para lembrar do seu horário.',
    ],
    'invite' => [
        'subject' => 'Finalize seu cadastro no linkcut',
        'intro' => 'Você agendou um horário pelo linkcut. Falta só criar sua senha.',
        'benefits' => 'Com o cadastro, você acompanha seus horários e agenda de novo com poucos toques.',
    ],
];

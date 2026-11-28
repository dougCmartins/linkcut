<?php

declare(strict_types=1);

return [
    'greeting' => 'Olá, :name!',
    'invite' => [
        'subject' => 'Convite para a equipe de :unit',
        'intro' => 'Você foi convidado para trabalhar em :unit no linkcut. Aceite o convite para criar seu acesso.',
        'action' => 'Aceitar convite',
        'expiry' => 'Este link é de uso único e expira em alguns dias. Se não foi você, ignore este e-mail.',
    ],
    'move' => [
        'subject' => 'Você agora atende em :unit',
        'intro' => 'A partir de :date você atende em :unit. Seu login continua o mesmo e já abre a agenda da nova filial.',
        'next' => 'O que você ganhou na filial anterior continua registrado lá.',
    ],
];

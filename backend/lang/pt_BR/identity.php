<?php

declare(strict_types=1);

return [
    'code_mail' => [
        'subject' => 'Seu código de acesso ao linkcut',
        'greeting' => 'Olá!',
        'intro' => 'Use este código para entrar no seu painel:',
        'expires' => 'Ele vale por :minutes minutos.',
        'ignore' => 'Se não foi você que tentou entrar, ignore este e-mail e troque sua senha.',
    ],
    'sign_up_mail' => [
        'subject' => 'Seu código para criar sua conta no linkcut',
        'intro' => 'Use este código para confirmar seu e-mail e criar a página da sua barbearia:',
        'ignore' => 'Se não foi você, ignore este e-mail: nenhuma conta é criada sem o código.',
    ],
    'login_link_mail' => [
        'subject' => 'Seu link de entrada no linkcut',
        'intro' => 'Você pediu o link para entrar no painel da :barbershop.',
        'action' => 'Entrar no painel',
        'tip' => 'Dica: salve este link nos favoritos ou adicione o app à tela inicial do celular.',
        'ignore' => 'Se não foi você, ignore este e-mail. Ninguém entra sem a sua senha e o código.',
    ],
    'account_exists_mail' => [
        'subject' => 'Você já tem uma conta no linkcut',
        'intro' => 'Alguém tentou criar uma conta com este e-mail, mas ele já tem uma. Para acessar seu painel, é só entrar.',
        'action' => 'Entrar no painel',
        'ignore' => 'Se não foi você, ignore este e-mail.',
    ],
    'code_sms' => 'linkcut: seu código de acesso é :code. Vale por :minutes min. Não compartilhe.',
];

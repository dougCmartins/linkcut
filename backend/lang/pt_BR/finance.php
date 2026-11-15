<?php

declare(strict_types=1);

return [
    // Header of the history CSV (same labels as the screen).
    'csv' => [
        'date' => 'Data',
        'time' => 'Hora',
        'client' => 'Cliente',
        'service' => 'Serviço',
        'value' => 'Valor (R$)',
    ],
    'report_mail' => [
        'subject' => 'Seu relatório do linkcut',
        'intro' => 'Segue em anexo o histórico de atendimentos de :from a :to, em CSV.',
        'excel' => 'Ele abre no Excel e no Google Planilhas, com os acentos e os valores certos.',
        'ignore' => 'Se não foi você que pediu, troque a sua senha no painel.',
    ],
];

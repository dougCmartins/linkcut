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
    'commission_csv' => [
        'staff_id' => 'Barbeiro (id)',
        'percent' => 'Comissão (%)',
        'base' => 'Base (R$)',
        'amount' => 'Valor (R$)',
        'booking_id' => 'Atendimento',
    ],
    'report_mail' => [
        'subject' => 'Seu relatório do linkcut',
        'intro' => 'Segue em anexo o histórico de atendimentos de :from a :to, em CSV.',
        'excel' => 'Ele abre no Excel e no Google Planilhas, com os acentos e os valores certos.',
        'ignore' => 'Se não foi você que pediu, troque a sua senha no painel.',
    ],
    'commission_report_mail' => [
        'subject' => 'Seu relatório de comissões do linkcut',
        'intro' => 'Segue em anexo o relatório de comissões de :from a :to, em CSV.',
    ],
];

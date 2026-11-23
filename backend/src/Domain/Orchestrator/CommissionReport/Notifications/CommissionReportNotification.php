<?php

declare(strict_types=1);

namespace Domain\Orchestrator\CommissionReport\Notifications;

use Carbon\CarbonImmutable;
use Domain\Finance\Data\CommissionReportQueryData;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CommissionReportNotification extends Notification
{
    public function __construct(
        private readonly CommissionReportQueryData $period,
        private readonly string $fileName,
        private readonly string $csv,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $format = fn (string $date): string => CarbonImmutable::createFromFormat('!Y-m-d', $date)->format('d/m/Y');

        return (new MailMessage)
            ->subject(__('finance.commission_report_mail.subject'))
            ->greeting(__('identity.code_mail.greeting'))
            ->line(__('finance.commission_report_mail.intro', [
                'from' => $format($this->period->from),
                'to' => $format($this->period->to),
            ]))
            ->line(__('finance.report_mail.excel'))
            ->line(__('finance.report_mail.ignore'))
            ->attachData($this->csv, $this->fileName, ['mime' => 'text/csv']);
    }
}

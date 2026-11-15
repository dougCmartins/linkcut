<?php

declare(strict_types=1);

namespace Domain\Orchestrator\HistoryReport\Notifications;

use Carbon\CarbonImmutable;
use Domain\Finance\Data\PeriodData;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The history report as a CSV attachment. Sent from inside the queued job, so it is not queued again. */
final class HistoryReportNotification extends Notification
{
    public function __construct(
        private readonly PeriodData $period,
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
            ->subject(__('finance.report_mail.subject'))
            ->greeting(__('identity.code_mail.greeting'))
            ->line(__('finance.report_mail.intro', ['from' => $format($this->period->from), 'to' => $format($this->period->to)]))
            ->line(__('finance.report_mail.excel'))
            ->line(__('finance.report_mail.ignore'))
            ->attachData($this->csv, $this->fileName, ['mime' => 'text/csv']);
    }
}

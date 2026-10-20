<?php

declare(strict_types=1);

namespace Domain\Notification\Notifications;

use Carbon\CarbonImmutable;
use Domain\Notification\Data\BookingEmailData;
use Domain\Notification\Enums\BookingEmailKind;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Request received, accepted, declined or the day-before reminder. Queued after the commit, encrypted. */
final class BookingEmailNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly BookingEmailData $data)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->data;
        $key = "client_mail.{$data->kind->value}";
        $replace = [
            'name' => $data->client_name,
            'barbershop' => $data->barbershop_name,
            'service' => $data->service_name,
            'date' => CarbonImmutable::parse($data->date)->format('d/m'),
            'time' => $data->time,
        ];

        $mail = (new MailMessage)
            ->subject(__("{$key}.subject", $replace))
            ->greeting(__('client_mail.greeting', $replace))
            ->line(__("{$key}.intro", $replace))
            ->line(__('client_mail.details', $replace))
            ->line(__('client_mail.address', ['address' => $data->barbershop_address]));

        $mail = match ($data->kind) {
            BookingEmailKind::Rejected => $mail->action(__('client_mail.book_again'), $data->booking_url),
            BookingEmailKind::Received => $data->registration_url
                ? $mail->line(__('client_mail.register_hint'))->action(__('client_mail.register'), $data->registration_url)
                : $mail,
            default => $mail->action(__('client_mail.directions'), $data->maps_url),
        };

        if ($data->whatsapp_url !== '' && $data->kind !== BookingEmailKind::Rejected) {
            $mail->line(__('client_mail.whatsapp', ['url' => $data->whatsapp_url]));
        }
        if ($data->opt_out_url) {
            $mail->line(__('client_mail.opt_out', ['url' => $data->opt_out_url]));
        }

        return $mail;
    }
}

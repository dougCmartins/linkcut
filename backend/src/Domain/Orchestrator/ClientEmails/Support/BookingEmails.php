<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientEmails\Support;

use Carbon\CarbonImmutable;
use Domain\Notification\Actions\NotifyClient;
use Domain\Notification\Actions\SendBookingEmail;
use Domain\Notification\Data\BookingEmailData;
use Domain\Notification\Data\ClientPushData;
use Domain\Notification\Data\PushMessageData;
use Domain\Notification\Enums\BookingEmailKind;
use Domain\Orchestrator\Barbershop\Actions\ShowBarbershopContact;
use Domain\Orchestrator\Barbershop\Data\BarbershopContactData;
use Domain\Schedule\Data\BookingRequestData;

/**
 * Tells the client about a booking of the current barbershop: the e-mail (skipped for old requests without e-mail)
 * and, for the barbershop's answers and the reminder, a push to the client's devices that asked for it.
 */
final class BookingEmails
{
    public function __construct(
        private readonly ShowBarbershopContact $showContact,
        private readonly SendBookingEmail $sendEmail,
        private readonly NotifyClient $notifyClient,
    ) {}

    /** @param BarbershopContactData|null $barbershop already read by the caller (batches), else read here */
    public function send(
        BookingRequestData $booking,
        BookingEmailKind $kind,
        ?string $registrationUrl = null,
        ?string $optOutUrl = null,
        ?BarbershopContactData $barbershop = null,
    ): void {
        if ($booking->client_email === '') {
            return;
        }
        $barbershop ??= $this->showContact->handle();
        $this->push($booking, $kind, $barbershop);

        $this->sendEmail->handle(new BookingEmailData(
            kind: $kind,
            client_name: $booking->client_name,
            client_email: $booking->client_email,
            service_name: $booking->service_name,
            date: $booking->date,
            time: $booking->starts_at,
            barbershop_name: $barbershop->name,
            barbershop_address: $barbershop->address,
            maps_url: $barbershop->maps_url,
            whatsapp_url: $barbershop->whatsapp_url,
            booking_url: $barbershop->booking_url,
            registration_url: $registrationUrl,
            opt_out_url: $optOutUrl,
            logo_url: self::absolute($barbershop->avatar_url),
            accent_color: $barbershop->accent_color,
        ));
    }

    private function push(BookingRequestData $booking, BookingEmailKind $kind, BarbershopContactData $barbershop): void
    {
        if ($booking->client_id === null || $kind === BookingEmailKind::Received) {
            return;
        }
        $replace = [
            'barbershop' => $barbershop->name,
            'service' => $booking->service_name,
            'date' => CarbonImmutable::parse($booking->date)->format('d/m'),
            'time' => $booking->starts_at,
        ];
        $this->notifyClient->handle(new ClientPushData(
            client_id: $booking->client_id,
            message: new PushMessageData(
                title: __("notification.client_{$kind->value}.title", $replace),
                body: __("notification.client_{$kind->value}.body", $replace),
                url: '/conta',
                tag: "booking-{$booking->id}",
            ),
        ));
    }

    /** E-mails need absolute image URLs ("/storage/x.webp" → "https://domain/storage/x.webp"). */
    private static function absolute(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return str_starts_with($url, 'http') ? $url : rtrim((string) config('app.frontend_url'), '/').'/'.ltrim($url, '/');
    }
}

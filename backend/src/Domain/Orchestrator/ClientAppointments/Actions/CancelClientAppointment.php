<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAppointments\Actions;

use Carbon\CarbonImmutable;
use Domain\Client\Models\Client;
use Domain\Notification\Actions\NotifyTenant;
use Domain\Notification\Data\PushMessageData;
use Domain\Notification\Data\TenantPushData;
use Domain\Orchestrator\ClientAppointments\Data\ClientAppointmentIdData;
use Domain\Schedule\Actions\CancelBookingRequest;
use Domain\Schedule\Actions\ShowClientBooking;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Schedule\Data\ClientBookingData;
use Domain\Schedule\Data\ClientBookingIdData;
use Domain\Schedule\Exceptions\CancellationTooLateException;
use Domain\Schedule\Exceptions\InvalidStatusTransitionException;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Context\TenantClock;

/**
 * "Cancelar" in the client area: only the client's own appointment, waiting or confirmed, and until
 * config('client.cancel_until_hours') before the time (in the barbershop's timezone). The time is free again
 * and the barber gets a push.
 */
final class CancelClientAppointment
{
    public function __construct(
        private readonly ShowClientBooking $showBooking,
        private readonly CancelBookingRequest $cancel,
        private readonly NotifyTenant $notifyTenant,
        private readonly CurrentTenant $currentTenant,
        private readonly TenantClock $clock,
    ) {}

    public function handle(ClientAppointmentIdData $data): void
    {
        /** @var Client $client */
        $client = auth('client')->user();
        $booking = $this->showBooking->handle(new ClientBookingIdData(booking_id: $data->id, client_id: $client->id));
        if (! $booking->status->isCancellableByClient()) {
            throw new InvalidStatusTransitionException;
        }

        $this->currentTenant->run($booking->tenant_id, function () use ($booking): void {
            $this->ensureNotTooLate($booking);
            $cancelled = $this->cancel->handle(new BookingRequestIdData(id: $booking->id), $booking->status);
            $this->notifyBarber($booking->tenant_id, $cancelled);
        });
    }

    private function ensureNotTooLate(ClientBookingData $booking): void
    {
        $now = $this->clock->now();
        $startsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', "{$booking->date} {$booking->starts_at}", $now->getTimezone());
        if ($now->addHours((int) config('client.cancel_until_hours')) > $startsAt) {
            throw new CancellationTooLateException;
        }
    }

    private function notifyBarber(int $tenantId, BookingRequestData $booking): void
    {
        $this->notifyTenant->handle(new TenantPushData(
            tenant_id: $tenantId,
            message: new PushMessageData(
                title: __('notification.booking_cancelled.title'),
                body: __('notification.booking_cancelled.body', [
                    'client' => $booking->client_name,
                    'service' => $booking->service_name,
                    'date' => CarbonImmutable::parse($booking->date)->format('d/m'),
                    'time' => $booking->starts_at,
                ]),
                url: '/painel',
                tag: "booking-request-{$booking->id}",
            ),
        ));
    }
}

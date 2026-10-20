<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientEmails\Actions;

use Domain\Client\Actions\ListOptedOutClients;
use Domain\Client\Data\ClientIdsData;
use Domain\Client\Support\ClientLinks;
use Domain\Notification\Enums\BookingEmailKind;
use Domain\Orchestrator\ClientEmails\Support\BookingEmails;
use Domain\Schedule\Actions\ListRemindableBookings;
use Domain\Schedule\Actions\MarkBookingReminded;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BookingRequestIdData;
use Domain\Schedule\Data\ReminderWindowData;
use Domain\Tenant\Actions\ListActiveTenants;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Context\TenantClock;
use Domain\Tenant\Data\TenantData;
use Illuminate\Support\Facades\Log;

/**
 * Hourly: the day-before reminder. For each active barbershop, confirmed appointments starting 20 to 28 hours
 * from now (its timezone) get one e-mail, unless the client opted out. Each one is marked, so it goes only once.
 */
final class SendBookingReminders
{
    public function __construct(
        private readonly ListActiveTenants $listTenants,
        private readonly CurrentTenant $currentTenant,
        private readonly TenantClock $clock,
        private readonly ListRemindableBookings $listBookings,
        private readonly ListOptedOutClients $listOptedOut,
        private readonly MarkBookingReminded $markReminded,
        private readonly BookingEmails $emails,
    ) {}

    /** @return int how many reminders were sent */
    public function handle(): int
    {
        $sent = 0;
        foreach ($this->listTenants->handle() as $tenant) {
            /** @var TenantData $tenant */
            $sent += $this->currentTenant->run($tenant->id, fn (): int => $this->remindTenant());
        }
        Log::info('client_emails_reminders_sent', ['count' => $sent]);

        return $sent;
    }

    private function remindTenant(): int
    {
        $now = $this->clock->now();
        $bookings = $this->listBookings->handle(new ReminderWindowData(
            from: $now->addHours(20)->format('Y-m-d H:i'),
            to: $now->addHours(28)->format('Y-m-d H:i'),
        ));
        $clientIds = collect($bookings->all())->pluck('client_id')->filter()->unique()->values()->all();
        $optedOut = $this->listOptedOut->handle(new ClientIdsData(ids: $clientIds));

        $sent = 0;
        foreach ($bookings as $booking) {
            /** @var BookingRequestData $booking */
            if ($booking->client_id !== null && in_array($booking->client_id, $optedOut, true)) {
                continue;
            }
            $optOutUrl = $booking->client_id !== null ? ClientLinks::optOut($booking->client_id) : null;
            $this->emails->send($booking, BookingEmailKind::Reminder, optOutUrl: $optOutUrl);
            $this->markReminded->handle(new BookingRequestIdData(id: $booking->id));
            $sent++;
        }

        return $sent;
    }
}

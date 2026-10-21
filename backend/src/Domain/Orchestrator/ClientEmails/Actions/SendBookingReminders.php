<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientEmails\Actions;

use Carbon\CarbonImmutable;
use Domain\Client\Actions\ListOptedOutClients;
use Domain\Client\Data\ClientIdsData;
use Domain\Client\Support\ClientLinks;
use Domain\Notification\Enums\BookingEmailKind;
use Domain\Orchestrator\Barbershop\Actions\ListBarbershopContacts;
use Domain\Orchestrator\ClientEmails\Support\BookingEmails;
use Domain\Schedule\Actions\ListRemindableBookings;
use Domain\Schedule\Actions\MarkBookingsReminded;
use Domain\Schedule\Data\BookingRequestIdsData;
use Domain\Schedule\Data\RemindableBookingData;
use Domain\Schedule\Data\ReminderWindowData;
use Domain\Tenant\Actions\ListActiveTenants;
use Domain\Tenant\Data\TenantData;
use Domain\Tenant\Data\TenantIdsData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Hourly: the day-before reminder. Confirmed appointments starting 20 to 28 hours from now (in the barbershop's
 * timezone) get one e-mail, unless the client opted out. Each one is marked, so it goes only once.
 * The queries don't grow with the number of appointments: bookings per timezone, contacts and opt-outs in batch,
 * and one UPDATE at the end.
 */
final class SendBookingReminders
{
    private const WINDOW_START_HOURS = 20;

    private const WINDOW_END_HOURS = 28;

    public function __construct(
        private readonly ListActiveTenants $listTenants,
        private readonly ListRemindableBookings $listBookings,
        private readonly ListBarbershopContacts $listContacts,
        private readonly ListOptedOutClients $listOptedOut,
        private readonly MarkBookingsReminded $markReminded,
        private readonly BookingEmails $emails,
    ) {}

    /** @return int how many reminders were sent */
    public function handle(): int
    {
        $due = $this->dueBookings();
        $contacts = $this->listContacts->handle(new TenantIdsData(ids: $due->pluck('tenant_id')->unique()->values()->all()));
        $optedOut = $this->optedOut($due);

        $sent = [];
        try {
            foreach ($due as $item) {
                $booking = $item->booking;
                if ($booking->client_id !== null && isset($optedOut[$booking->client_id])) {
                    continue;
                }
                $optOutUrl = $booking->client_id !== null ? ClientLinks::optOut($booking->client_id) : null;
                $this->emails->send($booking, BookingEmailKind::Reminder, optOutUrl: $optOutUrl, barbershop: $contacts[$item->tenant_id]);
                $sent[] = $booking->id;
            }
        } finally {
            // Also after a failure: what already went out is never sent twice.
            $this->markReminded->handle(new BookingRequestIdsData(ids: $sent));
        }
        Log::info('client_emails_reminders_sent', ['count' => count($sent)]);

        return count($sent);
    }

    /** @return Collection<int, RemindableBookingData> */
    private function dueBookings(): Collection
    {
        return collect($this->listTenants->handle()->all())
            ->groupBy(fn (TenantData $tenant): string => $tenant->timezone)
            ->flatMap(function (Collection $tenants, string $timezone): array {
                $now = CarbonImmutable::now($timezone !== '' ? $timezone : config('app.timezone'));

                return $this->listBookings->handle(new ReminderWindowData(
                    tenant_ids: $tenants->pluck('id')->all(),
                    from: $now->addHours(self::WINDOW_START_HOURS)->format('Y-m-d H:i'),
                    to: $now->addHours(self::WINDOW_END_HOURS)->format('Y-m-d H:i'),
                ))->all();
            })
            ->values();
    }

    /**
     * @param  Collection<int, RemindableBookingData>  $due
     * @return array<int, true> client ids that turned the e-mails off
     */
    private function optedOut(Collection $due): array
    {
        $clientIds = $due->map(fn (RemindableBookingData $item): ?int => $item->booking->client_id)->filter()->unique()->values()->all();
        if ($clientIds === []) {
            return [];
        }

        return array_fill_keys($this->listOptedOut->handle(new ClientIdsData(ids: $clientIds)), true);
    }
}

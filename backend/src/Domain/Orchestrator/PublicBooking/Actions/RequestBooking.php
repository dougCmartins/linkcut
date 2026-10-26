<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Actions;

use Carbon\CarbonImmutable;
use Domain\Client\Actions\FindOrCreateClient;
use Domain\Client\Data\ClientContactData;
use Domain\Client\Data\ClientData;
use Domain\Client\Support\ClientLinks;
use Domain\Client\Support\PhoneNumber;
use Domain\Notification\Actions\NotifyTenant;
use Domain\Notification\Data\PushMessageData;
use Domain\Notification\Data\TenantPushData;
use Domain\Notification\Enums\BookingEmailKind;
use Domain\Orchestrator\ClientEmails\Support\BookingEmails;
use Domain\Orchestrator\PublicBooking\Data\BookingConfirmationData;
use Domain\Orchestrator\PublicBooking\Data\BookingRequestInputData;
use Domain\Orchestrator\PublicBooking\Exceptions\BarbershopAddressMissingException;
use Domain\Orchestrator\PublicBooking\Support\AvailableTimes;
use Domain\Orchestrator\PublicBooking\Support\Distance;
use Domain\Schedule\Actions\CreateBookingRequest;
use Domain\Schedule\Actions\ListBusySlots;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\BusySlotData;
use Domain\Schedule\Data\DateRangeData;
use Domain\Schedule\Data\NewBookingRequestData;
use Domain\Schedule\Exceptions\SlotUnavailableException;
use Domain\Settings\Actions\ListContactProfiles;
use Domain\Settings\Actions\ShowBookingOptions;
use Domain\Settings\Data\AddressData;
use Domain\Settings\Data\BookingOptionsData;
use Domain\Settings\Data\PublicServiceData;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Context\TenantClock;
use Domain\Tenant\Data\TenantIdsData;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * A client asks for a time on the public page. The barbershop must have an address (the client saw where it is);
 * the time must be one of the free times now (hours, window, vacation, taken times); then Schedule saves it as
 * pending, checking again under a lock. When the client shared the location, only the distance is kept.
 * The booking belongs to a client account found (or created, without password) by e-mail. Then the barber gets
 * a push and the client the "request received" e-mail, with "Finalizar cadastro" while they have no password.
 */
final class RequestBooking
{
    public function __construct(
        private readonly ShowBookingOptions $showBookingOptions,
        private readonly ListBusySlots $listBusySlots,
        private readonly TenantClock $clock,
        private readonly CreateBookingRequest $createBookingRequest,
        private readonly ListContactProfiles $listProfiles,
        private readonly NotifyTenant $notifyTenant,
        private readonly CurrentTenant $currentTenant,
        private readonly FindOrCreateClient $findOrCreateClient,
        private readonly BookingEmails $emails,
    ) {}

    public function handle(BookingRequestInputData $data): BookingConfirmationData
    {
        // Options first: they also create the default settings of a new barbershop.
        $options = $this->showBookingOptions->handle();
        $serviceIds = array_map('intval', $data->service_ids);
        $service = AvailableTimes::services($options, $serviceIds);
        $profile = $this->listProfiles->handle(new TenantIdsData(ids: [$this->currentTenant->id()]))->first();
        $address = $profile?->address ?? throw new BarbershopAddressMissingException;
        $this->ensureSlotIsFree($data, $options, $service);

        $client = $this->findOrCreateClient->handle(new ClientContactData(
            name: trim($data->client_name),
            email: $data->client_email,
            phone: PhoneNumber::digits($data->client_whatsapp),
        ));
        $request = $this->createBookingRequest->handle(new NewBookingRequestData(
            client_name: trim($data->client_name),
            client_whatsapp: PhoneNumber::digits($data->client_whatsapp),
            service_id: $service->id,
            service_ids: $serviceIds,
            service_name: $service->name,
            duration_minutes: $service->duration_minutes,
            date: $data->date,
            starts_at: $data->time,
            client_email: $client->email,
            client_id: $client->id,
            client_distance_meters: self::distance($data, $address),
            price_in_cents: $service->price_in_cents,
            client_notes: self::notes($data->client_notes),
        ));

        $this->notifyBarber($request);
        $this->emails->send($request, BookingEmailKind::Received, registrationUrl: self::registrationUrl($client));

        return new BookingConfirmationData(
            id: $request->id,
            service_name: $request->service_name,
            date: $request->date,
            time: $request->starts_at,
            status: $request->status->value,
            push_url: URL::temporarySignedRoute('client-push.subscribe', now()->addHours(2), ['client' => $client->id], absolute: false),
        );
    }

    /** Only the requested day is checked (inside the booking window, not taken, not past). */
    private function ensureSlotIsFree(BookingRequestInputData $data, BookingOptionsData $options, PublicServiceData $service): void
    {
        $now = $this->clock->now();
        $today = $now->startOfDay();
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $data->date, $now->getTimezone());
        if (! $day || $day < $today || $day > $today->addDays($options->booking_window_days - 1)) {
            throw new SlotUnavailableException;
        }
        $busy = collect($this->listBusySlots->handle(new DateRangeData(from: $data->date, to: $data->date))->all())
            ->map(fn (BusySlotData $slot): array => [$slot->starts_at, $slot->ends_at])->values()->all();
        if (! in_array($data->time, AvailableTimes::forDate($options, $service, $day, $now, $busy), true)) {
            throw new SlotUnavailableException;
        }
    }

    /** Push to the barber's devices: who, what and when (opens the dashboard on tap). */
    private function notifyBarber(BookingRequestData $request): void
    {
        $this->notifyTenant->handle(new TenantPushData(
            tenant_id: $this->currentTenant->id(),
            message: new PushMessageData(
                title: __('notification.new_booking.title'),
                body: __('notification.new_booking.body', [
                    'client' => $request->client_name,
                    'service' => $request->service_name,
                    'date' => CarbonImmutable::parse($request->date)->format('d/m'),
                    'time' => $request->starts_at,
                ]).($request->client_notes ? ' · '.Str::limit($request->client_notes, 80) : ''),
                url: '/painel',
                tag: "booking-request-{$request->id}",
            ),
        ));
    }

    /** Plain text on one paragraph: spaces and line breaks squeezed, nothing when empty. */
    private static function notes(?string $notes): ?string
    {
        $clean = trim((string) preg_replace('/\s+/u', ' ', (string) $notes));

        return $clean === '' ? null : $clean;
    }

    private static function registrationUrl(ClientData $client): ?string
    {
        return $client->has_account ? null : ClientLinks::registration($client->email);
    }

    /** Meters between the client and the barbershop, when both points are known. */
    private static function distance(BookingRequestInputData $data, AddressData $address): ?int
    {
        if ($data->client_latitude === null || $data->client_longitude === null || $address->latitude === null || $address->longitude === null) {
            return null;
        }

        return Distance::meters($data->client_latitude, $data->client_longitude, $address->latitude, $address->longitude);
    }
}

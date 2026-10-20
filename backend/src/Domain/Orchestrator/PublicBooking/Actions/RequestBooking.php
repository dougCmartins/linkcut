<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Actions;

use Carbon\CarbonImmutable;
use Domain\Notification\Actions\NotifyTenant;
use Domain\Notification\Data\PushMessageData;
use Domain\Notification\Data\TenantPushData;
use Domain\Orchestrator\PublicBooking\Data\AvailabilityQueryData;
use Domain\Orchestrator\PublicBooking\Data\AvailableDayData;
use Domain\Orchestrator\PublicBooking\Data\BookingConfirmationData;
use Domain\Orchestrator\PublicBooking\Data\BookingRequestInputData;
use Domain\Orchestrator\PublicBooking\Exceptions\BarbershopAddressMissingException;
use Domain\Orchestrator\PublicBooking\Support\Distance;
use Domain\Schedule\Actions\CreateBookingRequest;
use Domain\Schedule\Data\BookingRequestData;
use Domain\Schedule\Data\NewBookingRequestData;
use Domain\Schedule\Exceptions\SlotUnavailableException;
use Domain\Settings\Actions\ShowBookingOptions;
use Domain\Settings\Actions\ShowProfileSettings;
use Domain\Settings\Data\AddressData;
use Domain\Settings\Data\PublicServiceData;
use Domain\Tenant\Context\CurrentTenant;

/**
 * A client asks for a time on the public page. The barbershop must have an address (the client saw where it is);
 * the time must be one of the free times now (hours, window, vacation, taken times); then Schedule saves it as
 * pending, checking again under a lock. When the client shared the location, only the distance is kept.
 * Then the barber gets a push notification (queued).
 */
final class RequestBooking
{
    public function __construct(
        private readonly ShowAvailability $showAvailability,
        private readonly ShowBookingOptions $showBookingOptions,
        private readonly CreateBookingRequest $createBookingRequest,
        private readonly ShowProfileSettings $showProfile,
        private readonly NotifyTenant $notifyTenant,
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(BookingRequestInputData $data): BookingConfirmationData
    {
        // Availability first: it also creates the default settings of a new barbershop.
        $availability = $this->showAvailability->handle(new AvailabilityQueryData(service_id: $data->service_id));
        $address = $this->showProfile->handle()->address ?? throw new BarbershopAddressMissingException;
        $day = collect($availability->days->all())->first(fn (AvailableDayData $day): bool => $day->date === $data->date);
        if (! $day || ! in_array($data->time, $day->times, true)) {
            throw new SlotUnavailableException;
        }

        /** @var PublicServiceData $service */
        $service = collect($this->showBookingOptions->handle()->services->all())
            ->first(fn (PublicServiceData $item): bool => $item->id === $data->service_id);
        $request = $this->createBookingRequest->handle(new NewBookingRequestData(
            client_name: trim($data->client_name),
            client_whatsapp: self::digits($data->client_whatsapp),
            service_id: $service->id,
            service_name: $service->name,
            duration_minutes: $service->duration_minutes,
            date: $data->date,
            starts_at: $data->time,
            client_distance_meters: self::distance($data, $address),
        ));

        $this->notifyBarber($request);

        return new BookingConfirmationData(
            id: $request->id,
            service_name: $request->service_name,
            date: $request->date,
            time: $request->starts_at,
            status: $request->status->value,
        );
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
                ]),
                url: '/painel',
                tag: "booking-request-{$request->id}",
            ),
        ));
    }

    /** Meters between the client and the barbershop, when both points are known. */
    private static function distance(BookingRequestInputData $data, AddressData $address): ?int
    {
        if ($data->client_latitude === null || $data->client_longitude === null || $address->latitude === null || $address->longitude === null) {
            return null;
        }

        return Distance::meters($data->client_latitude, $data->client_longitude, $address->latitude, $address->longitude);
    }

    /** Same storage as the barber's own WhatsApp: digits only, without the country code. */
    private static function digits(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return strlen($digits) > 11 && str_starts_with($digits, '55') ? substr($digits, 2) : $digits;
    }
}

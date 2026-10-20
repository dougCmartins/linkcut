<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Actions;

use Domain\Orchestrator\PublicBooking\Data\AvailabilityQueryData;
use Domain\Orchestrator\PublicBooking\Data\AvailableDayData;
use Domain\Orchestrator\PublicBooking\Data\BookingConfirmationData;
use Domain\Orchestrator\PublicBooking\Data\BookingRequestInputData;
use Domain\Schedule\Actions\CreateBookingRequest;
use Domain\Schedule\Data\NewBookingRequestData;
use Domain\Schedule\Exceptions\SlotUnavailableException;
use Domain\Settings\Actions\ShowBookingOptions;
use Domain\Settings\Data\PublicServiceData;

/**
 * A client asks for a time on the public page: the time must be one of the free times now
 * (hours, window, vacation, taken times), then Schedule saves it as pending, checking again under a lock.
 */
final class RequestBooking
{
    public function __construct(
        private readonly ShowAvailability $showAvailability,
        private readonly ShowBookingOptions $showBookingOptions,
        private readonly CreateBookingRequest $createBookingRequest,
    ) {}

    public function handle(BookingRequestInputData $data): BookingConfirmationData
    {
        $availability = $this->showAvailability->handle(new AvailabilityQueryData(service_id: $data->service_id));
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
        ));

        return new BookingConfirmationData(
            id: $request->id,
            service_name: $request->service_name,
            date: $request->date,
            time: $request->starts_at,
            status: $request->status->value,
        );
    }

    /** Same storage as the barber's own WhatsApp: digits only, without the country code. */
    private static function digits(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return strlen($digits) > 11 && str_starts_with($digits, '55') ? substr($digits, 2) : $digits;
    }
}

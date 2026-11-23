<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Actions;

use Carbon\CarbonImmutable;
use Domain\Client\Actions\FindOrCreateClient;
use Domain\Client\Data\ClientContactData;
use Domain\Client\Data\ClientData;
use Domain\Client\Support\ClientLinks;
use Domain\Client\Support\PhoneNumber;
use Domain\Notification\Actions\NotifyUsers;
use Domain\Notification\Data\PushMessageData;
use Domain\Notification\Data\UsersPushData;
use Domain\Notification\Enums\BookingEmailKind;
use Domain\Orchestrator\ClientEmails\Support\BookingEmails;
use Domain\Orchestrator\PublicBooking\Data\BookingConfirmationData;
use Domain\Orchestrator\PublicBooking\Data\BookingRequestInputData;
use Domain\Orchestrator\PublicBooking\Exceptions\BarbershopAddressMissingException;
use Domain\Orchestrator\PublicBooking\Support\AvailableTimes;
use Domain\Orchestrator\PublicBooking\Support\Distance;
use Domain\Orchestrator\PublicBooking\Support\StaffAvailability;
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
use Domain\Settings\Data\BusinessHourData;
use Domain\Settings\Data\PublicServiceData;
use Domain\Team\Actions\FindStaffById;
use Domain\Team\Actions\ListActiveStaffForServices;
use Domain\Team\Actions\ListStaffBlockRangesForDate;
use Domain\Team\Actions\ListStaffBusinessHours;
use Domain\Team\Data\StaffBusinessHourData;
use Domain\Team\Data\StaffIdData;
use Domain\Team\Models\Staff;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Context\TenantClock;
use Domain\Tenant\Data\TenantIdsData;
use Domain\Tenant\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * A client asks for a time on the public page. The slot must be free for the chosen barber
 * (or any available barber, picked inside the same lock transaction). Push goes to that barber and the owner.
 */
final class RequestBooking
{
    public function __construct(
        private readonly ShowBookingOptions $showBookingOptions,
        private readonly ListBusySlots $listBusySlots,
        private readonly TenantClock $clock,
        private readonly CreateBookingRequest $createBookingRequest,
        private readonly ListContactProfiles $listProfiles,
        private readonly NotifyUsers $notifyUsers,
        private readonly CurrentTenant $currentTenant,
        private readonly FindOrCreateClient $findOrCreateClient,
        private readonly BookingEmails $emails,
        private readonly ListActiveStaffForServices $listActiveStaff,
        private readonly ListStaffBusinessHours $listHours,
        private readonly ListStaffBlockRangesForDate $listBlocks,
        private readonly FindStaffById $findStaff,
    ) {}

    public function handle(BookingRequestInputData $data): BookingConfirmationData
    {
        $options = $this->showBookingOptions->handle();
        $serviceIds = array_map('intval', $data->service_ids);
        $service = AvailableTimes::services($options, $serviceIds);
        $profile = $this->listProfiles->handle(new TenantIdsData(ids: [$this->currentTenant->id()]))->first();
        $address = $profile?->address ?? throw new BarbershopAddressMissingException;

        $client = $this->findOrCreateClient->handle(new ClientContactData(
            name: trim($data->client_name),
            email: $data->client_email,
            phone: PhoneNumber::digits($data->client_whatsapp),
        ));

        $request = DB::transaction(function () use ($data, $options, $service, $serviceIds, $client, $address): BookingRequestData {
            Tenant::query()->whereKey($this->currentTenant->id())->lockForUpdate()->firstOrFail(['id']);
            $staffId = $this->resolveStaffId($data, $options, $service, $serviceIds);

            return $this->createBookingRequest->handle(new NewBookingRequestData(
                staff_id: $staffId,
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
        });

        $this->notifyBarberAndOwner($request);
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

    /** @param list<int> $serviceIds */
    private function resolveStaffId(
        BookingRequestInputData $data,
        BookingOptionsData $options,
        PublicServiceData $service,
        array $serviceIds,
    ): int {
        $candidates = $data->staff_id !== null
            ? [$data->staff_id]
            : $this->listActiveStaff->handle($serviceIds);

        if ($candidates === []) {
            throw new SlotUnavailableException;
        }

        if ($data->staff_id !== null) {
            $this->findStaff->handle(new StaffIdData(id: $data->staff_id));
            if (! $this->slotIsFreeForStaff($data->staff_id, $data, $options, $service)) {
                throw new SlotUnavailableException;
            }

            return $data->staff_id;
        }

        foreach ($candidates as $staffId) {
            if ($this->slotIsFreeForStaff($staffId, $data, $options, $service)) {
                return $staffId;
            }
        }

        throw new SlotUnavailableException;
    }

    private function slotIsFreeForStaff(
        int $staffId,
        BookingRequestInputData $data,
        BookingOptionsData $options,
        PublicServiceData $service,
    ): bool {
        $now = $this->clock->now();
        $today = $now->startOfDay();
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $data->date, $now->getTimezone());
        if (! $day || $day < $today || $day > $today->addDays($options->booking_window_days - 1)) {
            return false;
        }

        $hoursCollection = $this->listHours->handle(new StaffIdData(id: $staffId));
        /** @var StaffBusinessHourData|null $match */
        $match = collect($hoursCollection->all())->first(fn (StaffBusinessHourData $item): bool => $item->weekday === $day->dayOfWeek);
        $hours = $match === null ? null : new BusinessHourData(
            weekday: $match->weekday,
            is_open: $match->is_open,
            opens_at: $match->opens_at,
            closes_at: $match->closes_at,
        );

        $busy = collect($this->listBusySlots->handle(new DateRangeData(from: $data->date, to: $data->date), $staffId)->all())
            ->map(fn (BusySlotData $slot): array => [$slot->starts_at, $slot->ends_at])
            ->values()
            ->all();
        $blocks = $this->listBlocks->handle(new StaffIdData(id: $staffId), $data->date);
        $times = StaffAvailability::forDay(
            date: $data->date,
            hours: $hours,
            vacation: $options->vacation,
            intervalMinutes: $options->slot_interval_minutes,
            durationMinutes: $service->duration_minutes,
            busy: array_merge($busy, $blocks),
            nowMinutes: $day->isSameDay($now) ? $now->hour * 60 + $now->minute : null,
        );

        return in_array($data->time, $times, true);
    }

    private function notifyBarberAndOwner(BookingRequestData $request): void
    {
        $staff = Staff::query()->whereKey($request->staff_id)->first();
        $ownerUserId = Tenant::query()
            ->whereKey($this->currentTenant->id())
            ->join('organizations', 'organizations.id', '=', 'tenants.organization_id')
            ->value('organizations.owner_user_id');

        $userIds = array_values(array_unique(array_filter([
            $staff?->user_id,
            $ownerUserId ? (int) $ownerUserId : null,
        ])));

        $this->notifyUsers->handle(new UsersPushData(
            tenant_id: $this->currentTenant->id(),
            user_ids: $userIds,
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

    private static function notes(?string $notes): ?string
    {
        $clean = trim((string) preg_replace('/\s+/u', ' ', (string) $notes));

        return $clean === '' ? null : $clean;
    }

    private static function registrationUrl(ClientData $client): ?string
    {
        return $client->has_account || $client->google_linked ? null : ClientLinks::registration($client->email);
    }

    private static function distance(BookingRequestInputData $data, AddressData $address): ?int
    {
        if ($data->client_latitude === null || $data->client_longitude === null || $address->latitude === null || $address->longitude === null) {
            return null;
        }

        return Distance::meters($data->client_latitude, $data->client_longitude, $address->latitude, $address->longitude);
    }
}

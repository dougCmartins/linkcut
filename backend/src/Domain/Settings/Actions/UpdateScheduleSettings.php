<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\BusinessHourData;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Data\ScheduleSettingsData;
use Domain\Settings\Data\ServiceInputData;
use Domain\Settings\Data\UpdateScheduleSettingsData;
use Domain\Settings\Models\BookingSettings;
use Domain\Settings\Models\BusinessHour;
use Domain\Settings\Models\Service;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Saves the "Agenda" tab. Services are synced by id: known ids are updated, new ones created,
 * missing ones soft deleted (past bookings keep pointing to them).
 */
final class UpdateScheduleSettings
{
    public function __construct(
        private readonly CreateDefaultSettings $createDefaultSettings,
        private readonly ShowScheduleSettings $showSchedule,
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(UpdateScheduleSettingsData $data): ScheduleSettingsData
    {
        $this->ensureValidHours($data);
        $this->createDefaultSettings->handle(new DefaultSettingsData);

        DB::transaction(function () use ($data): void {
            $this->syncServices($data);

            $tenantId = $this->currentTenant->id();
            BusinessHour::query()->upsert(
                array_map(fn (BusinessHourData $hours): array => [
                    'tenant_id' => $tenantId,
                    'weekday' => $hours->weekday,
                    'is_open' => $hours->is_open,
                    'opens_at' => $hours->opens_at,
                    'closes_at' => $hours->closes_at,
                ], $data->weekly_hours->all()),
                uniqueBy: ['tenant_id', 'weekday'],
                update: ['is_open', 'opens_at', 'closes_at'],
            );

            BookingSettings::query()->firstOrFail()->update([
                'slot_interval_minutes' => $data->slot_interval_minutes,
                'booking_window_days' => $data->booking_window_days,
                'vacation_enabled' => $data->vacation->is_enabled,
                'vacation_starts_on' => $data->vacation->starts_on,
                'vacation_ends_on' => $data->vacation->ends_on,
                'vacation_message' => $data->vacation->message ?? '',
            ]);
        });

        Log::info('settings_schedule_updated', ['services' => $data->services->count()]);

        return $this->showSchedule->handle();
    }

    private function syncServices(UpdateScheduleSettingsData $data): void
    {
        $inputIds = array_filter(array_map(fn (ServiceInputData $input): ?int => $input->id, $data->services->all()));
        $existing = Service::query()->whereKey($inputIds)->get()->keyBy('id');
        $keptIds = [];
        foreach ($data->services as $position => $input) {
            /** @var ServiceInputData $input */
            $attributes = [
                'name' => $input->name,
                'duration_minutes' => $input->duration_minutes,
                'price_in_cents' => $input->price_in_cents,
                'cost_in_cents' => $input->cost_in_cents,
                'position' => $position,
            ];
            $service = $input->id ? $existing->get($input->id) : null;
            if ($service) {
                $service->update($attributes);
            } else {
                $service = Service::query()->create($attributes);
            }
            $keptIds[] = $service->id;
        }

        Service::query()->whereKeyNot($keptIds)->delete();
    }

    /** Rules that need the whole week: each weekday once, and an open day closes after it opens. */
    private function ensureValidHours(UpdateScheduleSettingsData $data): void
    {
        $seen = [];
        foreach ($data->weekly_hours as $index => $hours) {
            /** @var BusinessHourData $hours */
            if (isset($seen[$hours->weekday])) {
                throw ValidationException::withMessages(["weekly_hours.{$index}.weekday" => 'distinct']);
            }
            $seen[$hours->weekday] = true;

            if ($hours->is_open && $hours->closes_at <= $hours->opens_at) {
                throw ValidationException::withMessages(["weekly_hours.{$index}.closes_at" => 'after']);
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Data\UnitSettingsData;
use Domain\Settings\Models\BookingSettings;
use Domain\Settings\Models\BusinessHour;
use Domain\Settings\Models\PortfolioHighlight;
use Domain\Settings\Models\PortfolioSettings;
use Domain\Settings\Models\ProfileLink;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\Service;
use Domain\Settings\Models\SocialLink;
use Domain\Settings\Models\ThemeSettings;
use Domain\Settings\Support\PublicSettingsCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Settings of a new branch (the current tenant): the defaults, or a one-time copy of another unit's settings
 * (texts, theme, services, hours, booking rules and links; never images, which belong to that unit's files),
 * then its own name and address. Later changes in one unit never reach the other.
 */
final class SetUpUnitSettings
{
    /** What is copied from each settings table (tenant, ids, timestamps and image references stay behind). */
    private const COPIED = [
        SocialLink::class => ['network', 'url', 'position'],
        ProfileLink::class => ['title', 'url', 'icon', 'position'],
        PortfolioSettings::class => ['quote', 'story', 'show_rating', 'show_reviews'],
        PortfolioHighlight::class => ['label', 'value', 'position'],
        ThemeSettings::class => ['palette', 'mode', 'custom_accent', 'button_shape', 'button_style'],
        Service::class => ['name', 'duration_minutes', 'price_in_cents', 'cost_in_cents', 'position'],
        BusinessHour::class => ['weekday', 'is_open', 'opens_at', 'closes_at'],
        BookingSettings::class => ['slot_interval_minutes', 'booking_window_days', 'vacation_message'],
    ];

    private const COPIED_PROFILE = ['handle', 'bio', 'whatsapp_number', 'whatsapp_message'];

    public function __construct(
        private readonly CreateDefaultSettings $createDefaultSettings,
        private readonly PublicSettingsCache $cache,
    ) {}

    public function handle(UnitSettingsData $data): void
    {
        $profile = [
            'name' => trim($data->name),
            ...$data->address->columns(),
            'latitude' => $data->address->latitude,
            'longitude' => $data->address->longitude,
        ];

        DB::transaction(function () use ($data, $profile): void {
            if ($data->copy_from_tenant_id === null) {
                $this->createDefaultSettings->handle(new DefaultSettingsData(name: $profile['name']));
                ProfileSettings::query()->firstOrFail()->update($profile);

                return;
            }
            $source = ProfileSettings::withoutTenantScope()->where('tenant_id', $data->copy_from_tenant_id)->firstOrFail();
            ProfileSettings::insertForCurrentTenant([[...Arr::only($source->getAttributes(), self::COPIED_PROFILE), ...$profile]]);
            foreach (self::COPIED as $model => $columns) {
                $this->copy($model, $columns, $data->copy_from_tenant_id);
            }
        });

        $this->cache->forget();
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<string>  $columns
     */
    private function copy(string $model, array $columns, int $sourceTenantId): void
    {
        $rows = $model::withoutTenantScope()->where('tenant_id', $sourceTenantId)->orderBy('id')->get()
            ->map(fn (Model $row): array => Arr::only($row->getAttributes(), $columns))
            ->values()
            ->all();
        $model::insertForCurrentTenant($rows);
    }
}

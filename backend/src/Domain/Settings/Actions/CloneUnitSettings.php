<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Models\BookingSettings;
use Domain\Settings\Models\BusinessHour;
use Domain\Settings\Models\PortfolioHighlight;
use Domain\Settings\Models\PortfolioSettings;
use Domain\Settings\Models\ProfileLink;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\Service;
use Domain\Settings\Models\SocialLink;
use Domain\Settings\Models\ThemeSettings;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Support\Facades\DB;

/**
 * One-shot copy of another unit's settings into the current tenant.
 * Image ids are cleared: images stay owned by the source unit.
 */
final class CloneUnitSettings
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function handle(int $sourceTenantId): void
    {
        if ($this->currentTenant->id() === $sourceTenantId) {
            return;
        }

        $snapshot = $this->currentTenant->run($sourceTenantId, fn (): array => $this->snapshot());

        DB::transaction(function () use ($snapshot): void {
            if (ProfileSettings::query()->exists()) {
                return;
            }

            ProfileSettings::query()->create($snapshot['profile']);
            foreach ($snapshot['socials'] as $social) {
                SocialLink::query()->create($social);
            }
            foreach ($snapshot['links'] as $link) {
                ProfileLink::query()->create($link);
            }
            PortfolioSettings::query()->create($snapshot['portfolio']);
            foreach ($snapshot['highlights'] as $highlight) {
                PortfolioHighlight::query()->create($highlight);
            }
            ThemeSettings::query()->create($snapshot['theme']);
            foreach ($snapshot['services'] as $service) {
                Service::query()->create($service);
            }
            foreach ($snapshot['hours'] as $hours) {
                BusinessHour::query()->create($hours);
            }
            BookingSettings::query()->create($snapshot['booking']);
        });
    }

    /** @return array<string, mixed> */
    private function snapshot(): array
    {
        $profile = ProfileSettings::query()->firstOrFail();
        $portfolio = PortfolioSettings::query()->firstOrFail();
        $theme = ThemeSettings::query()->firstOrFail();
        $booking = BookingSettings::query()->firstOrFail();

        return [
            'profile' => [
                'name' => $profile->name,
                'handle' => $profile->handle,
                'bio' => $profile->bio,
                'avatar_image_id' => null,
                'background_image_id' => null,
                'whatsapp_number' => $profile->whatsapp_number,
                'whatsapp_message' => $profile->whatsapp_message,
                'address_zip_code' => $profile->address_zip_code,
                'address_street' => $profile->address_street,
                'address_number' => $profile->address_number,
                'address_complement' => $profile->address_complement,
                'address_district' => $profile->address_district,
                'address_city' => $profile->address_city,
                'address_state' => $profile->address_state,
                'latitude' => $profile->latitude,
                'longitude' => $profile->longitude,
            ],
            'socials' => SocialLink::query()->orderBy('position')->get()
                ->map(fn (SocialLink $row): array => $row->only(['network', 'url', 'position']))->all(),
            'links' => ProfileLink::query()->orderBy('position')->get()
                ->map(fn (ProfileLink $row): array => $row->only(['title', 'url', 'icon', 'position']))->all(),
            'portfolio' => [
                'cover_image_id' => null,
                'quote' => $portfolio->quote,
                'story' => $portfolio->story,
                'show_rating' => $portfolio->show_rating,
                'show_reviews' => $portfolio->show_reviews,
            ],
            'highlights' => PortfolioHighlight::query()->orderBy('position')->get()
                ->map(fn (PortfolioHighlight $row): array => $row->only(['label', 'value', 'position']))->all(),
            'theme' => $theme->only(['palette', 'mode', 'custom_accent', 'button_shape', 'button_style']),
            'services' => Service::query()->ordered()->get()
                ->map(fn (Service $row): array => $row->only(['name', 'duration_minutes', 'price_in_cents', 'cost_in_cents', 'position']))->all(),
            'hours' => BusinessHour::query()->orderBy('weekday')->get()
                ->map(fn (BusinessHour $row): array => $row->only(['weekday', 'is_open', 'opens_at', 'closes_at']))->all(),
            'booking' => $booking->only([
                'slot_interval_minutes', 'booking_window_days', 'vacation_enabled',
                'vacation_starts_on', 'vacation_ends_on', 'vacation_message',
            ]),
        ];
    }
}

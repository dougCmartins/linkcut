<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\ContactProfileData;
use Domain\Settings\Enums\ThemePalette;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\ThemeSettings;
use Domain\Tenant\Data\TenantIdsData;
use Domain\Tenant\Models\Concerns\TenantScope;
use Spatie\LaravelData\DataCollection;

/** Contact data of several barbershops in two queries, whatever their number (client area, e-mails). */
final class ListContactProfiles
{
    /** @return DataCollection<int, ContactProfileData> */
    public function handle(TenantIdsData $data): DataCollection
    {
        $accents = ThemeSettings::withoutTenantScope()
            ->whereIn('tenant_id', $data->ids)
            ->get(['tenant_id', 'palette', 'custom_accent'])
            ->mapWithKeys(fn (ThemeSettings $theme): array => [$theme->tenant_id => self::accentOf($theme)]);
        $profiles = ProfileSettings::withoutTenantScope()
            ->select(ProfileSettings::CONTACT_COLUMNS)
            ->whereIn('tenant_id', $data->ids)
            // Read for several barbershops at once (client area, e-mails): no current tenant to scope by.
            ->with(['avatar' => fn ($query) => $query->withoutGlobalScope(TenantScope::NAME)->select(['id', 'path'])])
            ->get()
            ->map(fn (ProfileSettings $profile): ContactProfileData => new ContactProfileData(
                tenant_id: $profile->tenant_id,
                name: $profile->name,
                whatsapp_number: $profile->whatsapp_number,
                address: $profile->address(),
                avatar_url: $profile->avatar?->url(),
                accent_color: $accents->get($profile->tenant_id),
            ));

        return ContactProfileData::collect($profiles, DataCollection::class);
    }

    private static function accentOf(ThemeSettings $theme): string
    {
        $custom = (string) $theme->custom_accent;

        $palette = $theme->palette instanceof ThemePalette ? $theme->palette : ThemePalette::Default;

        return preg_match('/^#[0-9a-f]{6}$/i', $custom) ? strtolower($custom) : $palette->lightAccent();
    }
}

<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\ContactProfileData;
use Domain\Settings\Models\ProfileSettings;
use Domain\Tenant\Data\TenantIdsData;
use Domain\Tenant\Models\Concerns\TenantScope;
use Spatie\LaravelData\DataCollection;

/** Contact data of several barbershops in two queries, whatever their number (client area, e-mails). */
final class ListContactProfiles
{
    /** @return DataCollection<int, ContactProfileData> */
    public function handle(TenantIdsData $data): DataCollection
    {
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
            ));

        return ContactProfileData::collect($profiles, DataCollection::class);
    }
}

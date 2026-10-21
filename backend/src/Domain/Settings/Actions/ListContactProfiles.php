<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\ContactProfileData;
use Domain\Settings\Models\ProfileSettings;
use Domain\Tenant\Data\TenantIdsData;
use Spatie\LaravelData\DataCollection;

/** Contact data of several barbershops in one query (client area, e-mails). */
final class ListContactProfiles
{
    /** @return DataCollection<int, ContactProfileData> */
    public function handle(TenantIdsData $data): DataCollection
    {
        $profiles = ProfileSettings::withoutTenantScope()
            ->select(ProfileSettings::CONTACT_COLUMNS)
            ->whereIn('tenant_id', $data->ids)
            ->get()
            ->map(fn (ProfileSettings $profile): ContactProfileData => new ContactProfileData(
                tenant_id: $profile->tenant_id,
                name: $profile->name,
                whatsapp_number: $profile->whatsapp_number,
                address: $profile->address(),
            ));

        return ContactProfileData::collect($profiles, DataCollection::class);
    }
}

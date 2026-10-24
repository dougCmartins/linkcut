<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Models\Image;
use Domain\Tenant\Data\TenantIdData;
use Illuminate\Support\Facades\Storage;

/** The uploaded files of a deleted barbershop (the rows go with the barbershop by cascade). */
final class DeleteTenantImageFiles
{
    public function handle(TenantIdData $data): void
    {
        Storage::disk(Image::DISK)->deleteDirectory("tenants/{$data->tenant_id}");
    }
}

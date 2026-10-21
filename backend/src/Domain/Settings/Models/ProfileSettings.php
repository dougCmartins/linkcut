<?php

declare(strict_types=1);

namespace Domain\Settings\Models;

use Domain\Settings\Data\AddressData;
use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bio link profile of the tenant (one row per tenant). */
final class ProfileSettings extends Model
{
    use BelongsToTenant;

    /** Enough to reach the barbershop (client area and e-mails). */
    public const CONTACT_COLUMNS = [
        'id', 'tenant_id', 'name', 'whatsapp_number', 'address_zip_code', 'address_street', 'address_number', 'address_complement',
        'address_district', 'address_city', 'address_state', 'latitude', 'longitude',
    ];

    protected $fillable = [
        'tenant_id', 'name', 'handle', 'bio', 'avatar_image_id', 'background_image_id', 'whatsapp_number', 'whatsapp_message',
        'address_zip_code', 'address_street', 'address_number', 'address_complement', 'address_district', 'address_city',
        'address_state', 'latitude', 'longitude',
    ];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }

    /** Barbershops created before the address was required have none until the barber fills it in. */
    public function hasAddress(): bool
    {
        return $this->address_street !== '';
    }

    public function address(): ?AddressData
    {
        return $this->hasAddress() ? new AddressData(
            zip_code: $this->address_zip_code,
            street: $this->address_street,
            number: $this->address_number,
            complement: $this->address_complement,
            district: $this->address_district,
            city: $this->address_city,
            state: $this->address_state,
            latitude: $this->latitude,
            longitude: $this->longitude,
        ) : null;
    }

    /** @return BelongsTo<Image, $this> */
    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'avatar_image_id');
    }

    /** @return BelongsTo<Image, $this> */
    public function background(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'background_image_id');
    }
}

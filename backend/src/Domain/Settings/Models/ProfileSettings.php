<?php

declare(strict_types=1);

namespace Domain\Settings\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bio link profile of the tenant (one row per tenant). */
final class ProfileSettings extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'handle', 'bio', 'avatar_image_id', 'background_image_id', 'whatsapp_number', 'whatsapp_message'];

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

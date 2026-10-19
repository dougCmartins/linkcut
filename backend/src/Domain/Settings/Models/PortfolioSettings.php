<?php

declare(strict_types=1);

namespace Domain\Settings\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Portfolio page texts and cover (one row per tenant). */
final class PortfolioSettings extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'cover_image_id', 'quote', 'story'];

    /** @return BelongsTo<Image, $this> */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'cover_image_id');
    }
}

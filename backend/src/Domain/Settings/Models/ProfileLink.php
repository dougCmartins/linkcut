<?php

declare(strict_types=1);

namespace Domain\Settings\Models;

use Domain\Settings\Enums\ProfileLinkIcon;
use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** A free link on the public page. Order is `position`. */
final class ProfileLink extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'title', 'url', 'icon', 'position'];

    protected function casts(): array
    {
        return ['icon' => ProfileLinkIcon::class, 'position' => 'integer'];
    }
}

<?php

declare(strict_types=1);

namespace Domain\Settings\Models;

use Domain\Settings\Enums\SocialNetwork;
use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** A social network link on the bio link (one per network). */
final class SocialLink extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'network', 'url', 'position'];

    protected function casts(): array
    {
        return ['network' => SocialNetwork::class, 'position' => 'integer'];
    }
}

<?php

declare(strict_types=1);

namespace Domain\Notification\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** A browser of a barber that receives push notifications (Web Push, VAPID). */
final class PushSubscription extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'user_id', 'endpoint', 'public_key', 'auth_token', 'content_encoding'];

    protected $hidden = ['public_key', 'auth_token'];

    protected function casts(): array
    {
        return ['user_id' => 'integer'];
    }
}

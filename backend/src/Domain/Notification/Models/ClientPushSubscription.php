<?php

declare(strict_types=1);

namespace Domain\Notification\Models;

use Illuminate\Database\Eloquent\Model;

/** A client's device that gets the barbershop's answers (accepted, declined, reminder). Clients are global. */
final class ClientPushSubscription extends Model
{
    protected $fillable = ['client_id', 'endpoint', 'public_key', 'auth_token', 'content_encoding'];

    protected function casts(): array
    {
        return ['client_id' => 'integer'];
    }
}

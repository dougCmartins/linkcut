<?php

declare(strict_types=1);

namespace Domain\Client\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

/**
 * A client of the barbershops (global, no tenant). Created at the first booking; signs in only after
 * completing the sign-up (password + e-mail confirmed by a code). Uses its own guard ("client").
 */
final class Client extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    use Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'password', 'email_verified_at', 'registration_invited_at', 'emails_opted_out_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'immutable_datetime',
            'registration_invited_at' => 'immutable_datetime',
            'emails_opted_out_at' => 'immutable_datetime',
        ];
    }

    public function hasAccount(): bool
    {
        return (bool) $this->password;
    }

    protected static function newFactory(): ClientFactory
    {
        return ClientFactory::new();
    }
}

<?php

declare(strict_types=1);

namespace Domain\Identity\Models;

use Database\Factories\UserFactory;
use Domain\Identity\Enums\TwoFactorChannel;
use Domain\Identity\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

/**
 * A barber who signs in to the dashboard. Belongs to one tenant (tenant_id),
 * but is not tenant-scoped: sign-in happens before any tenant is resolved.
 */
final class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    protected $fillable = ['tenant_id', 'role', 'name', 'email', 'phone', 'password', 'two_factor_channel', 'email_verified_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'two_factor_channel' => TwoFactorChannel::class,
        ];
    }

    /** Phone in E.164 for the SMS channel (null when the barber has no phone). */
    /** SMS is offered only when it is turned on (config sms.enabled) and the user has a phone. */
    public function canReceiveSms(): bool
    {
        return (bool) config('sms.enabled') && (bool) $this->phone;
    }

    /** @return list<TwoFactorChannel> */
    public function availableTwoFactorChannels(): array
    {
        return $this->canReceiveSms() ? [TwoFactorChannel::Email, TwoFactorChannel::Sms] : [TwoFactorChannel::Email];
    }

    public function routeNotificationForSms(): ?string
    {
        return $this->phone;
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}

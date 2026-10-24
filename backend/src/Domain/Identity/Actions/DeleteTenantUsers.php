<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Models\User;
use Domain\Tenant\Data\TenantIdData;

/**
 * Removes every barber of a barbershop that is closing its account: their access tokens (Passport keeps them in
 * a separate table) and the users; 2FA challenges, refresh tokens and push subscriptions go by cascade.
 */
final class DeleteTenantUsers
{
    public function handle(TenantIdData $data): void
    {
        User::query()->where('tenant_id', $data->tenant_id)->get()->each(function (User $user): void {
            $user->tokens()->delete();
            $user->delete();
        });
    }
}

<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\NewBarberData;
use Domain\Identity\Data\VerifiedUserData;
use Domain\Identity\Enums\UserRole;
use Domain\Identity\Exceptions\EmailAlreadyRegisteredException;
use Domain\Identity\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

/** Creates a barber user for one unit after a staff invite is accepted. */
final class CreateBarber
{
    public function handle(NewBarberData $data): VerifiedUserData
    {
        $email = mb_strtolower(trim($data->email));
        if (User::query()->where('email', $email)->exists()) {
            throw new EmailAlreadyRegisteredException;
        }
        try {
            $user = User::query()->create([
                'tenant_id' => $data->tenant_id,
                'organization_id' => $data->organization_id,
                'role' => UserRole::Barber,
                'name' => trim($data->name),
                'email' => $email,
                'password' => $data->password,
                'email_verified_at' => now(),
                'terms_accepted_at' => now(),
                'terms_version' => $data->terms_version,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new EmailAlreadyRegisteredException;
        }

        return new VerifiedUserData(user_id: $user->id, tenant_id: $user->tenant_id);
    }
}

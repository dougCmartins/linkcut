<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\NewOwnerData;
use Domain\Identity\Data\VerifiedUserData;
use Domain\Identity\Enums\UserRole;
use Domain\Identity\Exceptions\EmailAlreadyRegisteredException;
use Domain\Identity\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

/** The first user of a new barbershop (its owner), with the e-mail already confirmed. */
final class CreateOwner
{
    public function handle(NewOwnerData $data): VerifiedUserData
    {
        $email = mb_strtolower(trim($data->email));
        if (User::query()->where('email', $email)->exists()) {
            throw new EmailAlreadyRegisteredException;
        }
        try {
            $user = User::query()->create([
                'tenant_id' => $data->tenant_id,
                'role' => UserRole::Owner,
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

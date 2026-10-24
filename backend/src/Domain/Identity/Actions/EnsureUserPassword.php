<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\UserPasswordConfirmationData;
use Domain\Identity\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** Asks the signed-in barber's password again (a wrong one is a field error, not a 401). */
final class EnsureUserPassword
{
    public function handle(UserPasswordConfirmationData $data): void
    {
        /** @var User $user */
        $user = auth('api')->user();
        if (! Hash::check($data->password, (string) $user->password)) {
            throw ValidationException::withMessages(['password' => 'current_password']);
        }
    }
}

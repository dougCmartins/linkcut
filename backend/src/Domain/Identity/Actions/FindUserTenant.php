<?php

declare(strict_types=1);

namespace Domain\Identity\Actions;

use Domain\Identity\Data\EmailData;
use Domain\Identity\Models\User;

/** The barbershop of a barber's e-mail, if there is one (for "Encontrar minha página"). */
final class FindUserTenant
{
    public function handle(EmailData $data): ?int
    {
        return User::query()->where('email', mb_strtolower($data->email))->value('tenant_id');
    }
}

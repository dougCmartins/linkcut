<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Domain\Identity\Enums\TwoFactorChannel;
use Domain\Identity\Enums\UserRole;
use Spatie\LaravelData\Data;

final class UserData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone_masked,
        public readonly UserRole $role,
        public readonly ?TwoFactorChannel $two_factor_channel,
        /**
         * Channels this user can get the code by (SMS only when it is on and there is a phone).
         *
         * @var list<TwoFactorChannel>
         */
        public readonly array $available_channels,
    ) {}
}

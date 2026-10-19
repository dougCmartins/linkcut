<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Domain\Identity\Enums\TwoFactorChannel;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Data;

/** Default 2FA channel; null means "ask every time". */
final class UpdateTwoFactorChannelData extends Data
{
    public function __construct(
        #[Present, Nullable, Enum(TwoFactorChannel::class)]
        public readonly ?TwoFactorChannel $channel,
    ) {}
}

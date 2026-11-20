<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicPage\Data;

use Domain\Settings\Data\AddressData;
use Spatie\LaravelData\Data;

/** One unit on the brand link's picker: its own link, name, photo and address (all public on its page anyway). */
final class PublicBrandUnitData extends Data
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly bool $is_main,
        public readonly ?AddressData $address,
        public readonly ?string $avatar_url,
    ) {}
}

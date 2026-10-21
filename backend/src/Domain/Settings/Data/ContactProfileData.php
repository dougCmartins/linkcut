<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

/** What a client needs to reach a barbershop: its name, WhatsApp and address (no images or socials). */
final class ContactProfileData extends Data
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly string $whatsapp_number,
        public readonly ?AddressData $address,
    ) {}
}

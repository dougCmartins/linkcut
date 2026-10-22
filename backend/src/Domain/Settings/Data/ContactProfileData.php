<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

/** What a client needs to reach a barbershop: its name, logo, WhatsApp and address (no other images or socials). */
final class ContactProfileData extends Data
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly string $whatsapp_number,
        public readonly ?AddressData $address,
        /** The barbershop's photo or logo (null = none). */
        public readonly ?string $avatar_url = null,
    ) {}
}

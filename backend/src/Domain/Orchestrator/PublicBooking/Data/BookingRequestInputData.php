<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBooking\Data;

use Domain\Settings\Support\WhatsappNumberRule;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** What the client sends from the booking modal. */
final class BookingRequestInputData extends Data
{
    public function __construct(
        #[Required, StringType, Min(2), Max(60)]
        public readonly string $client_name,
        #[Required, StringType, Rule(new WhatsappNumberRule)]
        public readonly string $client_whatsapp,
        /** Where the client gets the answer and the reminder; also the client's account. */
        #[Required, StringType, Email, Max(120)]
        public readonly string $client_email,
        /** @var list<int> one or more services, done one after the other (times and prices add up) */
        #[Required, ArrayType, Min(1), Max(5)]
        public readonly array $service_ids,
        #[Required, DateFormat('Y-m-d')]
        public readonly string $date,
        #[Required, DateFormat('H:i')]
        public readonly string $time,
        /** Optional (null or absent): the client's location, only to compute the distance (never stored). No default value, so the rules below always run. */
        #[Nullable, Numeric, Between(-90, 90), RequiredWith('client_longitude')]
        public readonly ?float $client_latitude,
        #[Nullable, Numeric, Between(-180, 180), RequiredWith('client_latitude')]
        public readonly ?float $client_longitude,
        /** A note for the barber ("cabelo cacheado", "vou com meu filho"). */
        #[Nullable, StringType, Max(300)]
        public readonly ?string $client_notes = null,
    ) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return ['service_ids.*' => ['integer', 'min:1', 'distinct']];
    }
}

<?php

declare(strict_types=1);

namespace Domain\Settings\Support;

use Domain\Settings\Enums\ButtonShape;
use Domain\Settings\Enums\ButtonStyle;
use Domain\Settings\Enums\SocialNetwork;
use Domain\Settings\Enums\ThemeMode;
use Domain\Settings\Enums\ThemePalette;

/** Starting values of a new barbershop (the barber changes them in Settings). */
final class SettingsDefaults
{
    public const QUOTE = 'Um bom corte não muda quem você é. Ele mostra, com precisão, quem você já é.';

    public const STORY = "Comecei na cadeira do meu pai, aos 16 anos, aprendendo que cada cliente chega com uma história e sai com uma nova confiança.\n\nHoje atendo sozinho, um cliente por vez, sem pressa. Técnica clássica, acabamento na navalha e produtos premium.";

    public const WHATSAPP_MESSAGE = 'Olá! Vim pelo seu link e tenho uma dúvida.';

    public const VACATION_MESSAGE = 'Estou de férias! Volto em breve com a agenda aberta.';

    public const SLOT_INTERVAL_MINUTES = 30;

    public const BOOKING_WINDOW_DAYS = 7;

    /** @return list<array{label: string, value: string}> */
    public static function highlights(): array
    {
        return [
            ['label' => 'Estilo', 'value' => 'Clássico moderno'],
            ['label' => 'Foco', 'value' => 'Degradê & barba'],
            ['label' => 'Agenda', 'value' => 'Exclusiva'],
        ];
    }

    /** @return list<array{name: string, duration_minutes: int, price_in_cents: int, cost_in_cents: int}> */
    public static function services(): array
    {
        return [
            ['name' => 'Corte', 'duration_minutes' => 45, 'price_in_cents' => 5000, 'cost_in_cents' => 600],
            ['name' => 'Barba', 'duration_minutes' => 30, 'price_in_cents' => 4000, 'cost_in_cents' => 400],
        ];
    }

    /** Monday to Friday 09–19, Saturday 09–14, Sunday closed. @return list<array{weekday: int, is_open: bool, opens_at: string, closes_at: string}> */
    public static function weeklyHours(): array
    {
        return array_map(fn (int $weekday): array => match ($weekday) {
            0 => ['weekday' => 0, 'is_open' => false, 'opens_at' => '09:00', 'closes_at' => '18:00'],
            6 => ['weekday' => 6, 'is_open' => true, 'opens_at' => '09:00', 'closes_at' => '14:00'],
            default => ['weekday' => $weekday, 'is_open' => true, 'opens_at' => '09:00', 'closes_at' => '19:00'],
        }, range(0, 6));
    }

    /** @return list<SocialNetwork> */
    public static function socialNetworks(): array
    {
        return SocialNetwork::cases();
    }

    /** @return array<string, mixed> */
    public static function theme(): array
    {
        return [
            'palette' => ThemePalette::Default,
            'mode' => ThemeMode::Dark,
            'custom_accent' => null,
            'button_shape' => ButtonShape::Pill,
            'button_style' => ButtonStyle::Fill,
        ];
    }
}

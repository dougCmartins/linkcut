<?php

declare(strict_types=1);

namespace Domain\Settings\Tests\Concerns;

/** Valid request bodies for each settings tab; tests override one field at a time. */
trait SettingsPayloads
{
    /** @return array<string, mixed> */
    protected function profilePayload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Rafael Costa',
            'handle' => '@rafa.barber',
            'bio' => 'Cortes clássicos.',
            'avatar_image_id' => null,
            'background_image_id' => null,
            'whatsapp_number' => '(11) 98888-7777',
            'whatsapp_message' => 'Olá!',
            'socials' => [
                ['network' => 'instagram', 'url' => 'https://instagram.com/rafa'],
                ['network' => 'tiktok', 'url' => null],
            ],
        ], $overrides);
    }

    /** @return array<string, mixed> */
    protected function portfolioPayload(array $overrides = []): array
    {
        return array_replace([
            'cover_image_id' => null,
            'quote' => 'Frase.',
            'story' => "Primeiro parágrafo.\n\nSegundo.",
            'highlights' => [['label' => 'Estilo', 'value' => 'Clássico']],
            'photos' => [],
        ], $overrides);
    }

    /** @return array<string, mixed> */
    protected function schedulePayload(array $overrides = []): array
    {
        return array_replace([
            'services' => [['id' => null, 'name' => 'Corte', 'duration_minutes' => 45, 'price_in_cents' => 5000, 'cost_in_cents' => 600]],
            'weekly_hours' => array_map(fn (int $weekday): array => [
                'weekday' => $weekday,
                'is_open' => $weekday !== 0,
                'opens_at' => '09:00',
                'closes_at' => '18:00',
            ], range(0, 6)),
            'slot_interval_minutes' => 30,
            'booking_window_days' => 14,
            'vacation' => ['is_enabled' => false, 'starts_on' => null, 'ends_on' => null, 'message' => 'Férias!'],
        ], $overrides);
    }

    /** @return array<string, mixed> */
    protected function themePayload(array $overrides = []): array
    {
        return array_replace([
            'palette' => 'ocean',
            'mode' => 'light',
            'custom_accent' => '#FF8800',
            'button_shape' => 'rounded',
            'button_style' => 'outline',
        ], $overrides);
    }
}

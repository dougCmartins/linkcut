<?php

declare(strict_types=1);

namespace Domain\Settings\Tests\Feature;

use Domain\Identity\Models\User;
use Domain\Settings\Models\Service;
use Domain\Settings\Tests\Concerns\SettingsPayloads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class SettingsEditorTest extends TestCase
{
    use RefreshDatabase;
    use SettingsPayloads;

    private function actingAsBarber(): User
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        return $user;
    }

    public function test_first_access_creates_the_default_settings_with_the_private_cost(): void
    {
        // Arrange
        $this->actingAsBarber();

        // Act
        $response = $this->getJson('/api/settings');

        // Assert
        $response->assertOk();
        $response->assertJsonPath('schedule.services.0.name', 'Corte');
        $response->assertJsonPath('schedule.services.0.cost_in_cents', 600);
        $response->assertJsonCount(7, 'schedule.weekly_hours');
        $response->assertJsonPath('theme.button_shape', 'pill');
        $response->assertJsonCount(3, 'profile.socials');
    }

    public function test_saves_the_link_tab_and_keeps_only_the_phone_digits(): void
    {
        // Arrange
        $this->actingAsBarber();

        // Act
        $response = $this->putJson('/api/settings/profile', $this->profilePayload());

        // Assert
        $response->assertOk();
        $response->assertJsonPath('name', 'Rafael Costa');
        $response->assertJsonPath('whatsapp_number', '11988887777');
        $response->assertJsonPath('socials.0.url', 'https://instagram.com/rafa');
        $this->getJson('/api/settings')->assertJsonPath('profile.handle', '@rafa.barber');
    }

    public function test_the_link_tab_validates_name_phone_and_social_urls(): void
    {
        // Arrange
        $this->actingAsBarber();
        $payload = $this->profilePayload([
            'name' => 'R',
            'whatsapp_number' => '9999-8888',
            'socials' => [['network' => 'instagram', 'url' => 'http://inseguro.com']],
        ]);

        // Act
        $response = $this->putJson('/api/settings/profile', $payload);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('code', 'VALIDATION_FAILED');
        $response->assertJsonPath('errors.name.0', 'min');
        $response->assertJsonPath('errors.whatsapp_number.0', 'phone');
        $this->assertSame(['starts_with'], $response->json('errors')['socials.0.url']);
    }

    public function test_a_social_network_can_appear_only_once(): void
    {
        // Arrange
        $this->actingAsBarber();
        $socials = [['network' => 'instagram', 'url' => null], ['network' => 'instagram', 'url' => null]];

        // Act
        $response = $this->putJson('/api/settings/profile', $this->profilePayload(['socials' => $socials]));

        // Assert
        $response->assertUnprocessable();
        $this->assertSame(['distinct'], $response->json('errors')['socials.1.network']);
    }

    public function test_schedule_syncs_services_by_id_and_soft_deletes_the_removed_ones(): void
    {
        // Arrange
        $this->actingAsBarber();
        $services = $this->getJson('/api/settings')->json('schedule.services');
        $haircut = $services[0];
        $payload = $this->schedulePayload(['services' => [
            ['id' => $haircut['id'], 'name' => 'Corte degradê', 'duration_minutes' => 50, 'price_in_cents' => 6000, 'cost_in_cents' => 700],
            ['id' => null, 'name' => 'Sobrancelha', 'duration_minutes' => 15, 'price_in_cents' => 2000, 'cost_in_cents' => 0],
        ]]);

        // Act
        $response = $this->putJson('/api/settings/schedule', $payload);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('services.0.id', $haircut['id']);
        $response->assertJsonPath('services.0.name', 'Corte degradê');
        $response->assertJsonPath('services.1.name', 'Sobrancelha');
        $response->assertJsonCount(2, 'services');
        $this->assertTrue(Service::withoutTenantScope()->onlyTrashed()->whereKey($services[1]['id'])->exists());
    }

    public function test_schedule_rules_cost_below_price_valid_hours_and_vacation_dates(): void
    {
        // Arrange
        $this->actingAsBarber();
        $hours = $this->schedulePayload()['weekly_hours'];
        $hours[1]['closes_at'] = '08:00';
        $payload = $this->schedulePayload([
            'services' => [['id' => null, 'name' => 'Corte', 'duration_minutes' => 47, 'price_in_cents' => 5000, 'cost_in_cents' => 5000]],
            'slot_interval_minutes' => 20,
            'vacation' => ['is_enabled' => true, 'starts_on' => '2026-12-20', 'ends_on' => '2026-12-10', 'message' => null],
        ]);

        // Act
        $fieldErrors = $this->putJson('/api/settings/schedule', $payload)->json('errors');
        $hoursError = $this->putJson('/api/settings/schedule', $this->schedulePayload(['weekly_hours' => $hours]))->json('errors');

        // Assert
        $this->assertSame(['multiple_of'], $fieldErrors['services.0.duration_minutes']);
        $this->assertSame(['lt'], $fieldErrors['services.0.cost_in_cents']);
        $this->assertSame(['in'], $fieldErrors['slot_interval_minutes']);
        $this->assertSame(['after_or_equal'], $fieldErrors['vacation.ends_on']);
        $this->assertSame(['after'], $hoursError['weekly_hours.1.closes_at']);
    }

    public function test_the_week_must_have_each_weekday_once(): void
    {
        // Arrange
        $this->actingAsBarber();
        $hours = $this->schedulePayload()['weekly_hours'];
        $hours[6]['weekday'] = 5;

        // Act
        $response = $this->putJson('/api/settings/schedule', $this->schedulePayload(['weekly_hours' => $hours]));

        // Assert
        $response->assertUnprocessable();
        $this->assertSame(['distinct'], $response->json('errors')['weekly_hours.6.weekday']);
    }

    public function test_saves_the_theme_and_validates_the_accent_color(): void
    {
        // Arrange
        $this->actingAsBarber();

        // Act
        $saved = $this->putJson('/api/settings/theme', $this->themePayload());
        $invalid = $this->putJson('/api/settings/theme', $this->themePayload(['custom_accent' => 'orange', 'palette' => 'neon']));

        // Assert
        $saved->assertOk()->assertJsonPath('custom_accent', '#ff8800')->assertJsonPath('button_style', 'outline');
        $invalid->assertUnprocessable();
        $invalid->assertJsonPath('errors.custom_accent.0', 'regex');
        $invalid->assertJsonPath('errors.palette.0', 'enum');
    }

    public function test_portfolio_accepts_at_most_four_photos_with_a_description(): void
    {
        // Arrange
        $this->actingAsBarber();
        $photos = array_fill(0, 5, ['image_id' => 1, 'alt' => '']);

        // Act
        $response = $this->putJson('/api/settings/portfolio', $this->portfolioPayload(['photos' => $photos]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('errors.photos.0', 'max');
    }

    public function test_settings_require_a_session(): void
    {
        // Act
        $response = $this->getJson('/api/settings');

        // Assert
        $response->assertUnauthorized();
        $response->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_a_barbershop_never_sees_or_changes_the_settings_of_another(): void
    {
        // Arrange
        $owner = $this->actingAsBarber();
        $this->putJson('/api/settings/profile', $this->profilePayload(['name' => 'Barbearia A']))->assertOk();
        $otherBarber = User::factory()->create();

        // Act
        $this->app['auth']->forgetGuards();
        Passport::actingAs($otherBarber);
        $otherView = $this->getJson('/api/settings');
        $this->putJson('/api/settings/profile', $this->profilePayload(['name' => 'Barbearia B']))->assertOk();
        $this->app['auth']->forgetGuards();
        Passport::actingAs($owner);
        $ownerView = $this->getJson('/api/settings');

        // Assert
        $this->assertNotSame('Barbearia A', $otherView->json('profile.name'));
        $this->assertSame('Barbearia A', $ownerView->json('profile.name'));
    }
}

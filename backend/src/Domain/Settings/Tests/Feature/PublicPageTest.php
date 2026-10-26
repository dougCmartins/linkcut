<?php

declare(strict_types=1);

namespace Domain\Settings\Tests\Feature;

use Domain\Identity\Models\User;
use Domain\Settings\Tests\Concerns\SettingsPayloads;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class PublicPageTest extends TestCase
{
    use RefreshDatabase;
    use SettingsPayloads;

    private function barbershopWithSettings(string $slug): Tenant
    {
        $tenant = Tenant::factory()->create(['slug' => $slug]);
        Passport::actingAs(User::factory()->create(['tenant_id' => $tenant->id]));
        $this->putJson('/api/settings/profile', $this->profilePayload(['name' => "Barbearia {$slug}"]))->assertOk();
        $this->app['auth']->forgetGuards();

        return $tenant;
    }

    public function test_the_bio_link_of_a_barbershop_is_public_by_slug(): void
    {
        // Arrange
        $this->barbershopWithSettings('rafael');
        $this->barbershopWithSettings('navalha');

        // Act
        $response = $this->getJson('/api/public/rafael');

        // Assert
        $response->assertOk();
        $response->assertJsonPath('profile.name', 'Barbearia rafael');
        $response->assertJsonPath('profile.whatsapp_number', '11988887777');
        $response->assertJsonCount(1, 'profile.socials');
        $response->assertJsonPath('theme.palette', 'default');
    }

    public function test_booking_options_never_expose_the_service_cost(): void
    {
        // Arrange
        $this->barbershopWithSettings('rafael');

        // Act
        $response = $this->getJson('/api/public/rafael/booking-options');

        // Assert
        $response->assertOk();
        $response->assertJsonPath('services.0.price_in_cents', 5000);
        $response->assertJsonMissingPath('services.0.cost_in_cents');
        $this->assertStringNotContainsString('cost', $response->getContent());
        $response->assertJsonCount(7, 'weekly_hours');
    }

    public function test_a_repeated_visit_is_served_from_the_cache_of_that_barbershop(): void
    {
        // Arrange
        config(['cache.stores.array.serialize' => true]);
        $this->barbershopWithSettings('rafael');
        $this->barbershopWithSettings('navalha');
        $this->getJson('/api/public/rafael')->assertOk();
        $this->getJson('/api/public/rafael/booking-options')->assertOk();

        // Act
        DB::enableQueryLog();
        $page = $this->getJson('/api/public/rafael');
        $options = $this->getJson('/api/public/rafael/booking-options');
        $settingsQueries = array_filter(DB::getQueryLog(), fn (array $query): bool => ! str_contains($query['query'], '"tenants"'));
        $other = $this->getJson('/api/public/navalha');

        // Assert
        $page->assertOk()->assertJsonPath('profile.name', 'Barbearia rafael');
        $options->assertOk()->assertJsonPath('services.0.price_in_cents', 5000);
        $this->assertSame([], array_values($settingsQueries));
        $other->assertOk()->assertJsonPath('profile.name', 'Barbearia navalha');
    }

    public function test_visitors_see_saved_settings_right_away(): void
    {
        // Arrange
        config(['cache.stores.array.serialize' => true]);
        $tenant = $this->barbershopWithSettings('rafael');
        $this->getJson('/api/public/rafael')->assertJsonPath('profile.name', 'Barbearia rafael');
        Passport::actingAs(User::query()->where('tenant_id', $tenant->id)->firstOrFail());

        // Act
        $this->putJson('/api/settings/profile', $this->profilePayload(['name' => 'Navalha de Ouro']))->assertOk();
        $this->app['auth']->forgetGuards();
        $response = $this->getJson('/api/public/rafael');

        // Assert
        $response->assertOk()->assertJsonPath('profile.name', 'Navalha de Ouro');
    }

    public function test_a_returning_visitor_with_the_same_etag_gets_an_empty_not_modified(): void
    {
        // Arrange
        $this->barbershopWithSettings('rafael');
        $first = $this->getJson('/api/public/rafael');
        $etag = (string) $first->headers->get('ETag');

        // Act
        $again = $this->getJson('/api/public/rafael', ['If-None-Match' => $etag]);

        // Assert
        $this->assertNotSame('', $etag);
        $this->assertStringContainsString('no-cache', (string) $first->headers->get('Cache-Control'));
        $again->assertStatus(304);
        $this->assertSame('', $again->getContent());
    }

    public function test_an_unknown_or_deactivated_barbershop_is_not_found(): void
    {
        // Arrange
        Tenant::factory()->inactive()->create(['slug' => 'fechada']);

        // Act
        $unknown = $this->getJson('/api/public/nao-existe');
        $inactive = $this->getJson('/api/public/fechada');
        $malformed = $this->getJson('/api/public/Rafael%20Costa');

        // Assert
        $unknown->assertNotFound()->assertJsonPath('code', 'TENANT_NOT_FOUND');
        $inactive->assertNotFound()->assertJsonPath('code', 'TENANT_NOT_FOUND');
        $malformed->assertNotFound();
    }

    public function test_the_barber_reads_the_slug_of_the_own_barbershop(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create(['slug' => 'rafael']);
        Passport::actingAs(User::factory()->create(['tenant_id' => $tenant->id]));

        // Act
        $response = $this->getJson('/api/tenant');

        // Assert
        $response->assertOk();
        $response->assertJsonPath('slug', 'rafael');
        $response->assertJsonPath('timezone', 'America/Sao_Paulo');
    }
}

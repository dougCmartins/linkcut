<?php

declare(strict_types=1);

namespace Domain\Settings\Tests\Feature;

use Domain\Identity\Models\User;
use Domain\Settings\Tests\Concerns\SettingsPayloads;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class AddressTest extends TestCase
{
    use RefreshDatabase;
    use SettingsPayloads;

    private const NOMINATIM = 'nominatim.test/*';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.nominatim.url' => 'https://nominatim.test']);
        Passport::actingAs(User::factory()->create());
    }

    /** Address typed by the barber (no device location), so the API looks for the point. */
    private function typedAddress(array $overrides = []): array
    {
        return $this->profilePayload(['address' => $this->addressPayload(['latitude' => null, 'longitude' => null, ...$overrides])]);
    }

    public function test_the_address_is_required_and_validated(): void
    {
        // Arrange
        $withoutAddress = $this->profilePayload();
        unset($withoutAddress['address']);
        $invalid = $this->profilePayload(['address' => $this->addressPayload(['zip_code' => '01304-001', 'state' => 'XX', 'street' => '', 'longitude' => null])]);

        // Act
        $missing = $this->putJson('/api/settings/profile', $withoutAddress);
        $wrong = $this->putJson('/api/settings/profile', $invalid);

        // Assert
        $missing->assertUnprocessable()->assertJsonPath('errors.address.0', 'required');
        $errors = $wrong->assertUnprocessable()->json('errors');
        $this->assertSame(['regex'], $errors['address.zip_code']);
        $this->assertSame(['in'], $errors['address.state']);
        $this->assertSame(['required'], $errors['address.street']);
        $this->assertSame(['required_with'], $errors['address.longitude']);
    }

    public function test_finds_the_point_on_the_map_of_a_typed_address(): void
    {
        // Arrange
        Http::fake([self::NOMINATIM => Http::response([['lat' => '-23.5577', 'lon' => '-46.6608']])]);

        // Act
        $response = $this->putJson('/api/settings/profile', $this->typedAddress());

        // Assert
        $response->assertOk();
        $response->assertJsonPath('address.latitude', -23.5577);
        $response->assertJsonPath('address.longitude', -46.6608);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'countrycodes=br')
            && str_contains(urldecode($request->url()), 'Rua Augusta, 1500, Consolação, São Paulo - SP')
            && str_starts_with($request->header('User-Agent')[0], 'linkcut/'));
    }

    public function test_an_address_not_found_on_the_map_is_saved_without_the_point(): void
    {
        // Arrange
        Http::fake([self::NOMINATIM => Http::response([])]);

        // Act
        $response = $this->putJson('/api/settings/profile', $this->typedAddress());

        // Assert
        $response->assertOk();
        $response->assertJsonPath('address.street', 'Rua Augusta');
        $response->assertJsonPath('address.latitude', null);
        Http::assertSentCount(2);
    }

    public function test_the_map_service_being_down_never_blocks_saving(): void
    {
        // Arrange
        Http::fake([self::NOMINATIM => fn () => throw new ConnectionException('timeout')]);

        // Act
        $response = $this->putJson('/api/settings/profile', $this->typedAddress());

        // Assert
        $response->assertOk()->assertJsonPath('address.latitude', null);
    }

    public function test_the_device_location_wins_and_an_unchanged_address_is_not_looked_up_again(): void
    {
        // Arrange
        Http::fake([self::NOMINATIM => Http::response([['lat' => '-1', 'lon' => '-1']])]);

        // Act
        $fromDevice = $this->putJson('/api/settings/profile', $this->profilePayload());
        $sameAddress = $this->putJson('/api/settings/profile', $this->typedAddress());

        // Assert
        $fromDevice->assertJsonPath('address.latitude', -23.5578);
        $sameAddress->assertJsonPath('address.latitude', -23.5578);
        Http::assertNothingSent();
    }

    public function test_a_changed_address_is_looked_up_again(): void
    {
        // Arrange
        Http::fake([self::NOMINATIM => Http::response([['lat' => '-22.9661', 'lon' => '-43.1832']])]);
        $this->putJson('/api/settings/profile', $this->profilePayload())->assertOk();

        // Act
        $response = $this->putJson('/api/settings/profile', $this->typedAddress(['city' => 'Rio de Janeiro', 'state' => 'RJ']));

        // Assert
        $response->assertJsonPath('address.latitude', -22.9661);
        $response->assertJsonPath('address.city', 'Rio de Janeiro');
    }

    public function test_the_public_page_shows_the_address(): void
    {
        // Arrange
        $slug = Tenant::query()->firstOrFail()->slug;
        $this->putJson('/api/settings/profile', $this->profilePayload())->assertOk();

        // Act
        $response = $this->getJson("/api/public/{$slug}");

        // Assert
        $response->assertJsonPath('profile.address.street', 'Rua Augusta');
        $response->assertJsonPath('profile.address.number', '1500');
        $response->assertJsonPath('profile.address.latitude', -23.5578);
    }
}

<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BarbershopAccount\Tests\Feature;

use Domain\Client\Models\Client;
use Domain\Finance\Models\RevenueEntry;
use Domain\Identity\Models\User;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Models\BookingRequest;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Settings\Models\Image;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\Service;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** LGPD for barbers: download every data of the barbershop, or delete the account and everything in it. */
final class BarbershopAccountTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparePassport();
        Storage::fake(Image::DISK);
    }

    /** A barbershop with a photo (file on disk), a booking and a revenue entry. */
    private function barbershopWithData(string $slug = 'rafael'): User
    {
        $owner = $this->barbershop($slug);
        app(CurrentTenant::class)->run($owner->tenant_id, function () use ($owner): void {
            Storage::disk(Image::DISK)->put("tenants/{$owner->tenant_id}/images/foto.webp", 'img');
            $image = Image::query()->create(['path' => "tenants/{$owner->tenant_id}/images/foto.webp", 'mime' => 'image/webp', 'size' => 3, 'width' => 1, 'height' => 1]);
            ProfileSettings::query()->firstOrFail()->update(['avatar_image_id' => $image->id]);
            RevenueEntry::query()->create(['date' => '2026-10-12', 'time' => '10:00', 'client_name' => 'Ana', 'service_name' => 'Corte', 'price_in_cents' => 5000]);
        });
        $this->bookingRequest($owner, '2026-10-21', '10:00', BookingStatus::Confirmed);

        return $owner;
    }

    public function test_the_barber_downloads_every_data_of_the_barbershop(): void
    {
        // Arrange
        $owner = $this->barbershopWithData();
        $this->actingAsOwner($owner);

        // Act
        $response = $this->get('/api/account/export', ['Accept' => 'application/json']);

        // Assert
        $response->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="linkcut-meus-dados.json"');
        $response->assertJsonPath('barbershop.slug', 'rafael')
            ->assertJsonPath('account.email', $owner->email)
            ->assertJsonPath('bookings.0.service_name', 'Corte')
            ->assertJsonPath('finance.revenue_entries.0.price_in_cents', 5000)
            ->assertJsonStructure(['exported_at', 'settings' => ['profile', 'schedule', 'theme']]);
    }

    public function test_deleting_needs_the_password_and_the_link_typed_again(): void
    {
        // Arrange
        $owner = $this->barbershopWithData();
        $this->actingAsOwner($owner);

        // Act
        $wrongPassword = $this->deleteJson('/api/account', ['password' => 'errada123', 'slug_confirmation' => 'rafael']);
        $wrongSlug = $this->deleteJson('/api/account', ['password' => 'password', 'slug_confirmation' => 'outra']);

        // Assert
        $wrongPassword->assertUnprocessable()->assertJsonValidationErrors('password');
        $wrongSlug->assertUnprocessable()->assertJsonValidationErrors('slug_confirmation');
        $this->assertSame(1, Tenant::query()->count());
    }

    public function test_deleting_removes_everything_of_the_barbershop_and_nothing_of_the_others(): void
    {
        // Arrange
        $owner = $this->barbershopWithData();
        $other = $this->barbershopWithData('navalha');
        $client = Client::factory()->registered()->create();
        $this->actingAsOwner($owner);

        // Act
        $response = $this->deleteJson('/api/account', ['password' => 'password', 'slug_confirmation' => ' Rafael ']);

        // Assert
        $response->assertNoContent();
        $this->assertNull(Tenant::query()->find($owner->tenant_id));
        $this->assertNull(User::query()->find($owner->id));
        foreach ([BookingRequest::class, RevenueEntry::class, Service::class, ProfileSettings::class, Image::class] as $model) {
            $this->assertSame(0, $model::withoutTenantScope()->where('tenant_id', $owner->tenant_id)->count(), $model);
            $this->assertGreaterThan(0, $model::withoutTenantScope()->where('tenant_id', $other->tenant_id)->count(), $model);
        }
        Storage::disk(Image::DISK)->assertMissing("tenants/{$owner->tenant_id}/images/foto.webp");
        Storage::disk(Image::DISK)->assertExists("tenants/{$other->tenant_id}/images/foto.webp");
        $this->assertNotNull($client->fresh());
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/login', ['email' => $owner->email, 'password' => 'password'])->assertUnauthorized();
    }
}

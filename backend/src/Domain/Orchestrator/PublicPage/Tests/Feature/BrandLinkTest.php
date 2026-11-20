<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicPage\Tests\Feature;

use Domain\Orchestrator\PublicPage\Tests\Concerns\BrandFixtures;
use Domain\Schedule\Models\BookingRequest;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** The brand link with the branch picker (docs/decisoes/0001 P2). */
final class BrandLinkTest extends TestCase
{
    use BrandFixtures;
    use RefreshDatabase;
    use ScheduleFixtures;

    public function test_the_brand_link_lists_its_active_units_main_first_with_name_and_address(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $this->branch($owner, 'rafael-paulista', 'Avenida Paulista');
        $this->branch($owner, 'rafael-centro', 'Rua Direita');
        $this->branch($owner, 'rafael-fechada', active: false);

        // Act
        $response = $this->getJson('/api/public/brand/rafael');

        // Assert
        $response->assertOk()
            ->assertJsonPath('slug', 'rafael')
            ->assertJsonCount(3, 'units')
            ->assertJsonPath('units.0.slug', 'rafael')
            ->assertJsonPath('units.0.is_main', true)
            ->assertJsonPath('units.1.slug', 'rafael-centro')
            ->assertJsonPath('units.1.address.street', 'Rua Direita')
            ->assertJsonPath('units.2.name', 'Rafael-paulista');
    }

    public function test_a_barber_on_their_own_or_an_unknown_slug_has_no_brand_link(): void
    {
        // Arrange
        $this->barbershop('rafael');

        // Act
        $individual = $this->getJson('/api/public/brand/rafael');
        $unknown = $this->getJson('/api/public/brand/ninguem');

        // Assert
        $individual->assertNotFound();
        $unknown->assertNotFound();
    }

    public function test_only_the_main_unit_page_is_a_brand_link_and_every_unit_keeps_its_own_page(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $this->barbershop('solo');
        $this->branch($owner, 'rafael-paulista');

        // Act
        $main = $this->getJson('/api/public/rafael');
        $branch = $this->getJson('/api/public/rafael-paulista');
        $individual = $this->getJson('/api/public/solo');

        // Assert
        $main->assertOk()->assertJsonPath('brand_slug', 'rafael');
        $branch->assertOk()->assertJsonPath('brand_slug', null)->assertJsonPath('profile.name', 'Rafael-paulista');
        $individual->assertOk()->assertJsonPath('brand_slug', null);
    }

    public function test_a_booking_made_with_a_branch_picked_goes_to_that_branch(): void
    {
        // Arrange
        Notification::fake();
        $this->freezeMondayMorning('10:00');
        $owner = $this->barbershop('rafael');
        $branch = $this->branch($owner, 'rafael-paulista');
        $serviceId = $this->serviceId($owner->replicate()->forceFill(['tenant_id' => $branch->id]), 'Corte');

        // Act
        $response = $this->postJson('/api/public/rafael-paulista/booking-requests', [
            'client_name' => 'João Silva',
            'client_whatsapp' => '(11) 91234-5678',
            'client_email' => 'joao@example.com',
            'service_ids' => [$serviceId],
            'date' => '2026-10-20',
            'time' => '10:00',
        ]);

        // Assert
        $response->assertCreated();
        $this->assertSame(1, app(CurrentTenant::class)->run($branch->id, fn (): int => BookingRequest::query()->count()));
        $this->assertSame(0, app(CurrentTenant::class)->run($owner->tenant_id, fn (): int => BookingRequest::query()->count()));
    }
}

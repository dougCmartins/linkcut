<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAccount\Tests\Feature;

use Domain\Client\Models\Client;
use Domain\Finance\Models\RevenueEntry;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Models\BookingRequest;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** "Minha conta": name and WhatsApp, password, e-mail reminders and deleting the account (LGPD). */
final class ClientAccountTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    public function test_the_client_changes_name_and_whatsapp(): void
    {
        // Arrange
        $client = $this->signedInClient();

        // Act
        $response = $this->patchJson('/api/client/me', ['name' => '  Ana Lima ', 'phone' => '+55 (11) 98888-7777']);

        // Assert
        $response->assertOk()->assertJsonPath('name', 'Ana Lima')->assertJsonPath('phone', '11988887777');
        $this->assertSame('11988887777', $client->refresh()->phone);
    }

    public function test_a_whatsapp_with_too_few_digits_is_refused(): void
    {
        // Arrange
        $this->signedInClient();

        // Act
        $response = $this->patchJson('/api/client/me', ['name' => 'Ana', 'phone' => '(11) 9888-77']);

        // Assert
        $response->assertUnprocessable()->assertJsonPath('code', 'VALIDATION_FAILED')->assertJsonValidationErrors('phone');
    }

    public function test_the_password_changes_only_with_the_current_one(): void
    {
        // Arrange
        $client = $this->signedInClient();

        // Act
        $wrong = $this->putJson('/api/client/me/password', ['current_password' => 'errada123', 'password' => 'novasenha1']);
        $right = $this->putJson('/api/client/me/password', ['current_password' => 'segredo123', 'password' => 'novasenha1']);

        // Assert
        $wrong->assertUnprocessable();
        $right->assertNoContent();
        $this->assertTrue(Hash::check('novasenha1', $client->refresh()->password));
    }

    public function test_the_client_turns_the_e_mail_reminders_off_and_on_again(): void
    {
        // Arrange
        $client = $this->signedInClient();

        // Act
        $off = $this->putJson('/api/client/me/emails', ['enabled' => false]);
        $offAt = $client->refresh()->emails_opted_out_at;
        $on = $this->putJson('/api/client/me/emails', ['enabled' => true]);

        // Assert
        $off->assertOk()->assertJsonPath('emails_enabled', false);
        $this->assertNotNull($offAt);
        $on->assertOk()->assertJsonPath('emails_enabled', true);
        $this->assertNull($client->refresh()->emails_opted_out_at);
    }

    public function test_deleting_the_account_removes_the_personal_data_from_every_barbershop(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $client = $this->signedInClient();
        $booking = $this->bookingRequest($owner, '2026-10-12', '10:00', BookingStatus::Completed, attributes: [
            'client_id' => $client->id, 'client_email' => $client->email,
        ]);
        app(CurrentTenant::class)->run($owner->tenant_id, fn () => RevenueEntry::query()->create([
            'booking_request_id' => $booking->id, 'date' => '2026-10-12', 'time' => '10:00',
            'client_name' => 'Ana Souza', 'service_name' => 'Corte', 'price_in_cents' => 5000,
        ]));

        // Act
        $wrong = $this->deleteJson('/api/client/me', ['password' => 'errada123']);
        $deleted = $this->deleteJson('/api/client/me', ['password' => 'segredo123']);

        // Assert
        $wrong->assertUnprocessable();
        $deleted->assertNoContent();
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $anonymized = BookingRequest::withoutTenantScope()->findOrFail($booking->id);
        $this->assertSame(['Cliente removido', '', '', null], [$anonymized->client_name, $anonymized->client_whatsapp, $anonymized->client_email, $anonymized->client_id]);
        $this->assertSame('Cliente removido', RevenueEntry::withoutTenantScope()->where('booking_request_id', $booking->id)->value('client_name'));
        $this->assertSame(5000, RevenueEntry::withoutTenantScope()->where('booking_request_id', $booking->id)->value('price_in_cents'));
        $this->postJson('/api/client/login', ['email' => $client->email, 'password' => 'segredo123'])->assertUnauthorized();
    }

    private function signedInClient(): Client
    {
        $client = Client::factory()->registered()->create(['name' => 'Ana Souza', 'phone' => '11912345678']);
        Passport::actingAs($client, [], 'client');

        return $client;
    }
}

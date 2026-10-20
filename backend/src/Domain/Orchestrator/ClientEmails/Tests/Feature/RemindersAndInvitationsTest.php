<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientEmails\Tests\Feature;

use Carbon\CarbonImmutable;
use Domain\Client\Models\Client;
use Domain\Client\Support\ClientLinks;
use Domain\Notification\Enums\BookingEmailKind;
use Domain\Notification\Notifications\BookingEmailNotification;
use Domain\Notification\Notifications\RegistrationInviteNotification;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Models\BookingRequest;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** The scheduled e-mails: the day-before reminder and the single sign-up invitation. */
final class RemindersAndInvitationsTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning('10:00');
        Notification::fake();
    }

    public function test_reminds_confirmed_appointments_20_to_28_hours_ahead_only_once(): void
    {
        // Arrange: now is Monday 10:00 (São Paulo), so the window is Tue 06:00 to Tue 14:00.
        $owner = $this->barbershop();
        $client = Client::factory()->create(['email' => 'ana@example.com']);
        $this->bookingRequest($owner, '2026-10-20', '10:00', BookingStatus::Confirmed, attributes: ['client_email' => 'ana@example.com', 'client_id' => $client->id]);
        $this->bookingRequest($owner, '2026-10-20', '15:00', BookingStatus::Confirmed, attributes: ['client_email' => 'late@example.com']);
        $this->bookingRequest($owner, '2026-10-20', '11:00', BookingStatus::Pending, attributes: ['client_email' => 'pending@example.com']);
        $this->bookingRequest($owner, '2026-10-19', '18:00', BookingStatus::Confirmed, attributes: ['client_email' => 'today@example.com']);
        $this->bookingRequest($owner, '2026-10-20', '12:00', BookingStatus::Confirmed);

        // Act
        $this->artisan('schedule:send-reminders')->expectsOutput('Reminders sent: 1')->assertSuccessful();
        $this->artisan('schedule:send-reminders')->expectsOutput('Reminders sent: 0')->assertSuccessful();

        // Assert
        $sent = [];
        Notification::assertSentOnDemand(BookingEmailNotification::class, function (BookingEmailNotification $notification) use (&$sent): bool {
            $sent[] = $notification;

            return true;
        });
        $this->assertCount(1, $sent);
        $this->assertSame('ana@example.com', $sent[0]->data->client_email);
        $this->assertSame(BookingEmailKind::Reminder, $sent[0]->data->kind);
        $this->assertStringContainsString('/api/client/emails/opt-out/'.$client->id, (string) $sent[0]->data->opt_out_url);
        $mail = $sent[0]->toMail(new AnonymousNotifiable);
        $this->assertSame('Lembrete: seu horário 20/10 às 10:00 em Minha barbearia', $mail->subject);
        $this->assertNotNull(BookingRequest::withoutTenantScope()->where('client_email', 'ana@example.com')->value('reminded_at'));
    }

    public function test_uses_the_timezone_of_each_barbershop_and_skips_inactive_ones(): void
    {
        // Arrange: 10:00 in São Paulo is 14:00 in Lisbon, so the window there is Tue 10:00 to Tue 18:00.
        $lisbon = $this->barbershop('lisboa');
        Tenant::query()->whereKey($lisbon->tenant_id)->update(['timezone' => 'Europe/Lisbon']);
        $inactive = $this->barbershop('fechada');
        Tenant::query()->whereKey($inactive->tenant_id)->update(['active' => false]);
        $this->bookingRequest($lisbon, '2026-10-20', '09:00', BookingStatus::Confirmed, attributes: ['client_email' => 'early@example.com']);
        $this->bookingRequest($lisbon, '2026-10-20', '17:00', BookingStatus::Confirmed, attributes: ['client_email' => 'lisbon@example.com']);
        $this->bookingRequest($inactive, '2026-10-20', '10:00', BookingStatus::Confirmed, attributes: ['client_email' => 'closed@example.com']);

        // Act
        $this->artisan('schedule:send-reminders')->expectsOutput('Reminders sent: 1')->assertSuccessful();

        // Assert
        Notification::assertSentOnDemand(BookingEmailNotification::class, fn (BookingEmailNotification $notification): bool => $notification->data->client_email === 'lisbon@example.com');
        Notification::assertSentOnDemandTimes(BookingEmailNotification::class, 1);
    }

    public function test_a_client_who_opted_out_gets_no_reminder(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $client = Client::factory()->create(['email' => 'ana@example.com', 'emails_opted_out_at' => now()]);
        $this->bookingRequest($owner, '2026-10-20', '10:00', BookingStatus::Confirmed, attributes: ['client_email' => 'ana@example.com', 'client_id' => $client->id]);

        // Act
        $this->artisan('schedule:send-reminders')->expectsOutput('Reminders sent: 0')->assertSuccessful();

        // Assert
        Notification::assertNothingSent();
    }

    public function test_the_signed_opt_out_link_stops_reminders_and_a_tampered_one_is_refused(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $signed = ClientLinks::optOut($client->id);

        // Act
        $tampered = $this->get(str_replace("/opt-out/{$client->id}", '/opt-out/'.($client->id + 1), $signed));
        $response = $this->get($signed);

        // Assert
        $tampered->assertForbidden();
        $response->assertRedirect('http://localhost:5173/conta/lembretes-desativados');
        $this->assertNotNull($client->fresh()->emails_opted_out_at);
    }

    public function test_invites_once_only_clients_without_password_who_booked_over_two_hours_ago(): void
    {
        // Arrange
        $old = Client::factory()->create(['email' => 'old@example.com', 'created_at' => now()->subHours(3)]);
        Client::factory()->create(['email' => 'recent@example.com', 'created_at' => now()->subHour()]);
        Client::factory()->registered()->create(['email' => 'member@example.com', 'created_at' => now()->subDay()]);
        Client::factory()->create(['email' => 'out@example.com', 'created_at' => now()->subDay(), 'emails_opted_out_at' => now()]);

        // Act
        $this->artisan('client:invite-registration')->expectsOutput('Invitations sent: 1')->assertSuccessful();
        $this->travelTo(CarbonImmutable::now()->addDay());
        $this->artisan('client:invite-registration')->expectsOutput('Invitations sent: 1')->assertSuccessful();

        // Assert: the second run only invites "recent" (now old enough); "old" is never invited twice.
        Notification::assertSentOnDemandTimes(RegistrationInviteNotification::class, 2);
        Notification::assertSentOnDemand(RegistrationInviteNotification::class, function (RegistrationInviteNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool {
            return $notifiable->routeNotificationFor('mail') === 'old@example.com'
                && $notification->data->registration_url === 'http://localhost:5173/conta/finalizar?email=old%40example.com'
                && $notification->toMail($notifiable)->actionText === 'Finalizar cadastro';
        });
        $this->assertNotNull($old->fresh()->registration_invited_at);
    }
}

<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientEmails\Tests\Feature;

use Domain\Client\Models\Client;
use Domain\Notification\Enums\BookingEmailKind;
use Domain\Notification\Notifications\BookingEmailNotification;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Models\BookingRequest;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Settings\Models\Image;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\ThemeSettings;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Booking links the client account; the client gets "received", "accepted" and "declined" e-mails. */
final class BookingEmailsTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning();
        Notification::fake();
    }

    /** @return array<string, mixed> */
    private function payload(int $serviceId, string $email = 'Ana@Example.com', string $time = '10:00'): array
    {
        return ['client_name' => 'Ana Souza', 'client_whatsapp' => '11912345678', 'client_email' => $email, 'service_ids' => [$serviceId], 'date' => '2026-10-20', 'time' => $time];
    }

    /** @return list<BookingEmailNotification> */
    private function emailsTo(string $email): array
    {
        $sent = [];
        Notification::assertSentOnDemand(BookingEmailNotification::class, function (BookingEmailNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($email, &$sent): bool {
            if ($notifiable->routeNotificationFor('mail') === $email) {
                $sent[] = $notification;
            }

            return true;
        });

        return $sent;
    }

    public function test_booking_creates_the_client_by_e_mail_and_sends_received_with_the_sign_up_link(): void
    {
        // Arrange
        $owner = $this->barbershop();

        // Act
        $response = $this->postJson('/api/public/rafael/booking-requests', $this->payload($this->serviceId($owner, 'Corte')));

        // Assert
        $response->assertCreated();
        $client = Client::query()->sole();
        $this->assertSame('ana@example.com', $client->email);
        $this->assertSame('Ana Souza', $client->name);
        $this->assertFalse($client->hasAccount());
        $booking = BookingRequest::withoutTenantScope()->sole();
        $this->assertSame($client->id, $booking->client_id);
        $this->assertSame('ana@example.com', $booking->client_email);

        [$email] = $this->emailsTo('ana@example.com');
        $this->assertSame(BookingEmailKind::Received, $email->data->kind);
        $this->assertSame('http://localhost:5173/conta/finalizar?email=ana%40example.com', $email->data->registration_url);
        $this->assertSame('Rua Augusta, 1500 – Consolação, São Paulo/SP', $email->data->barbershop_address);
        $this->assertStringContainsString('-23.5578', urldecode($email->data->maps_url));
        $this->assertSame('http://localhost:5173/rafael', $email->data->booking_url);
        $mail = $email->toMail(new AnonymousNotifiable);
        $this->assertStringContainsString('Recebemos seu pedido', $mail->subject);
        $this->assertSame('Finalizar cadastro', $mail->actionText);
    }

    public function test_the_client_e_mail_has_the_barbershops_name_photo_and_color_and_linkcut_only_in_the_footer(): void
    {
        // Arrange
        $owner = $this->barbershop();
        app(CurrentTenant::class)->run($owner->tenant_id, function (): void {
            $image = Image::query()->create(['path' => 'tenants/1/images/rafa.webp', 'mime' => 'image/webp', 'size' => 10, 'width' => 400, 'height' => 400]);
            ProfileSettings::query()->firstOrFail()->update(['name' => 'Barbearia do Rafa', 'avatar_image_id' => $image->id]);
            ThemeSettings::query()->firstOrFail()->update(['palette' => 'ocean', 'custom_accent' => null]);
        });
        $this->postJson('/api/public/rafael/booking-requests', $this->payload($this->serviceId($owner, 'Corte')))->assertCreated();
        $notifiable = (new AnonymousNotifiable)->route('mail', 'ana@example.com');

        // Act
        $mail = $this->emailsTo('ana@example.com')[0]->toMail($notifiable);
        $html = (string) $mail->render();

        // Assert
        $this->assertSame([config('mail.from.address'), 'Barbearia do Rafa via linkcut'], $mail->from);
        $this->assertStringContainsString('Barbearia do Rafa', $html);
        $this->assertStringContainsString('/storage/tenants/1/images/rafa.webp', $html);
        $this->assertStringContainsString('background-color: #0b6fa4', $html);
        $this->assertStringContainsString('Enviado pelo linkcut para Barbearia do Rafa.', $html);
    }

    public function test_a_second_booking_with_the_same_e_mail_reuses_the_account_and_a_registered_client_gets_no_sign_up_link(): void
    {
        // Arrange
        $owner = $this->barbershop();
        Client::factory()->registered()->create(['email' => 'ana@example.com', 'name' => 'Ana Original']);

        // Act
        $this->postJson('/api/public/rafael/booking-requests', $this->payload($this->serviceId($owner, 'Corte')))->assertCreated();
        $this->postJson('/api/public/rafael/booking-requests', $this->payload($this->serviceId($owner, 'Barba'), 'ANA@example.com', '14:00'))->assertCreated();

        // Assert
        $client = Client::query()->sole();
        $this->assertSame('Ana Original', $client->name, 'a booking form never changes an existing account');
        $this->assertSame(2, BookingRequest::withoutTenantScope()->where('client_id', $client->id)->count());
        $emails = $this->emailsTo('ana@example.com');
        $this->assertCount(2, $emails);
        $this->assertNull($emails[0]->data->registration_url);
    }

    public function test_the_e_mail_is_required_and_valid(): void
    {
        // Arrange
        $owner = $this->barbershop();

        // Act
        $response = $this->postJson('/api/public/rafael/booking-requests', $this->payload($this->serviceId($owner, 'Corte'), 'not-an-email'));

        // Assert
        $response->assertUnprocessable()->assertJsonPath('errors.client_email.0', 'email');
        Notification::assertNothingSent();
    }

    public function test_accepting_and_declining_e_mail_the_client(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $accepted = $this->bookingRequest($owner, '2026-10-20', '10:00', attributes: ['client_email' => 'ana@example.com']);
        $declined = $this->bookingRequest($owner, '2026-10-20', '14:00', attributes: ['client_email' => 'bia@example.com']);
        $this->actingAsOwner($owner);

        // Act
        $this->patchJson("/api/schedule/requests/{$accepted->id}/accept")->assertOk()->assertJsonPath('status', 'confirmed');
        $this->patchJson("/api/schedule/requests/{$declined->id}/reject")->assertOk()->assertJsonPath('status', 'rejected');

        // Assert
        [$confirmation] = $this->emailsTo('ana@example.com');
        $this->assertSame(BookingEmailKind::Accepted, $confirmation->data->kind);
        $this->assertSame('Como chegar', $confirmation->toMail(new AnonymousNotifiable)->actionText);
        [$rejection] = $this->emailsTo('bia@example.com');
        $this->assertSame(BookingEmailKind::Rejected, $rejection->data->kind);
        $rejectionMail = $rejection->toMail(new AnonymousNotifiable);
        $this->assertSame('Escolher outro horário', $rejectionMail->actionText);
        $this->assertSame('http://localhost:5173/rafael', $rejectionMail->actionUrl);
    }

    public function test_an_old_request_without_e_mail_is_answered_without_sending_anything(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $request = $this->bookingRequest($owner, '2026-10-20', '10:00');
        $this->actingAsOwner($owner);

        // Act
        $response = $this->patchJson("/api/schedule/requests/{$request->id}/accept");

        // Assert
        $response->assertOk();
        Notification::assertNothingSent();
    }

    public function test_an_invalid_transition_sends_nothing(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $request = $this->bookingRequest($owner, '2026-10-20', '10:00', BookingStatus::Confirmed, attributes: ['client_email' => 'ana@example.com']);
        $this->actingAsOwner($owner);

        // Act
        $response = $this->patchJson("/api/schedule/requests/{$request->id}/accept");

        // Assert
        $response->assertConflict();
        Notification::assertNothingSent();
        $this->assertTrue(app(CurrentTenant::class)->run($owner->tenant_id, fn (): bool => BookingRequest::query()->whereKey($request->id)->value('status') === BookingStatus::Confirmed));
    }
}

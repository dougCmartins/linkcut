<?php

declare(strict_types=1);

namespace Domain\Orchestrator\SignIn\Tests\Feature;

use Domain\Identity\Models\User;
use Domain\Identity\Notifications\LoginLinkNotification;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class LoginLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_e_mails_the_sign_in_link_of_the_barbers_own_page(): void
    {
        // Arrange
        Notification::fake();
        config(['app.frontend_url' => 'https://linkcut.app']);
        $tenant = Tenant::factory()->create(['slug' => 'rafael', 'name' => 'Barbearia do Rafa']);
        User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'rafael@example.com']);

        // Act
        $response = $this->postJson('/api/login/link', ['email' => 'Rafael@Example.com']);

        // Assert
        $response->assertStatus(202)->assertJson(['sent' => true]);
        Notification::assertSentOnDemand(LoginLinkNotification::class, function (LoginLinkNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool {
            $mail = $notification->toMail($notifiable);

            return $notifiable->routeNotificationFor('mail') === 'rafael@example.com'
                && $mail->actionUrl === 'https://linkcut.app/rafael/panel/login'
                && str_contains(implode(' ', $mail->introLines), 'Barbearia do Rafa');
        });
    }

    public function test_answers_the_same_for_an_unknown_e_mail_or_an_inactive_barbershop_and_sends_nothing(): void
    {
        // Arrange
        Notification::fake();
        $inactive = Tenant::factory()->create(['active' => false]);
        User::factory()->create(['tenant_id' => $inactive->id, 'email' => 'off@example.com']);

        // Act
        $unknown = $this->postJson('/api/login/link', ['email' => 'nobody@example.com']);
        $deactivated = $this->postJson('/api/login/link', ['email' => 'off@example.com']);

        // Assert
        $unknown->assertStatus(202)->assertJson(['sent' => true]);
        $deactivated->assertStatus(202)->assertJson(['sent' => true]);
        Notification::assertNothingSent();
    }
}

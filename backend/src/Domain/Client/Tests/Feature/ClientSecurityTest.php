<?php

declare(strict_types=1);

namespace Domain\Client\Tests\Feature;

use Domain\Client\Models\Client;
use Domain\Client\Notifications\ClientCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Brute force on the e-mail codes and the sessions after a password change. */
final class ClientSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparePassport();
        Notification::fake();
    }

    private function lastCode(Client $client): string
    {
        $code = '';
        Notification::assertSentTo($client, ClientCodeNotification::class, function (ClientCodeNotification $notification) use ($client, &$code): bool {
            $line = collect($notification->toMail($client)->introLines)->first(fn (string $text): bool => (bool) preg_match('/^\*\*\d{6}\*\*$/', $text));
            $code = trim((string) $line, '*');

            return true;
        });

        return $code;
    }

    /** Asks for a new code and tries a wrong one five times (the limit of one code). */
    private function burnACode(Client $client): void
    {
        $this->travel(2)->minutes();
        $this->postJson('/api/client/codes', ['email' => $client->email, 'purpose' => 'password'])->assertSuccessful();
        $wrong = $this->lastCode($client) === '000000' ? '111111' : '000000';
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/client/password', ['email' => $client->email, 'code' => $wrong, 'password' => 'novasenha9']);
        }
    }

    public function test_asking_for_new_codes_does_not_give_new_guesses_for_the_day(): void
    {
        // Arrange: two codes, five wrong guesses each (10 = the daily limit).
        $client = Client::factory()->registered()->create();
        $this->burnACode($client);
        $this->burnACode($client);
        $this->travel(2)->minutes();
        $this->postJson('/api/client/codes', ['email' => $client->email, 'purpose' => 'password'])->assertSuccessful();

        // Act: even the right code of a fresh e-mail is refused today.
        $response = $this->postJson('/api/client/password', ['email' => $client->email, 'code' => $this->lastCode($client), 'password' => 'novasenha9']);

        // Assert
        $response->assertStatus(429)->assertJsonPath('code', 'TOO_MANY_ATTEMPTS');
        $this->postJson('/api/client/login', ['email' => $client->email, 'password' => 'segredo123'])->assertSuccessful();
    }

    public function test_the_next_day_the_right_code_works_again(): void
    {
        // Arrange
        $client = Client::factory()->registered()->create();
        $this->burnACode($client);
        $this->burnACode($client);
        $this->travel(25)->hours();
        $this->postJson('/api/client/codes', ['email' => $client->email, 'purpose' => 'password'])->assertSuccessful();

        // Act
        $response = $this->postJson('/api/client/password', ['email' => $client->email, 'code' => $this->lastCode($client), 'password' => 'novasenha9']);

        // Assert
        $response->assertSuccessful();
    }

    public function test_changing_the_password_signs_out_the_other_devices_and_keeps_this_one(): void
    {
        // Arrange: signed in on two devices.
        $client = Client::factory()->registered()->create();
        $here = $this->postJson('/api/client/login', ['email' => $client->email, 'password' => 'segredo123'])->json();
        $other = $this->postJson('/api/client/login', ['email' => $client->email, 'password' => 'segredo123'])->json();

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$here['access_token']}")
            ->putJson('/api/client/me/password', ['current_password' => 'segredo123', 'password' => 'novasenha9']);

        // Assert
        $response->assertOk()->assertJsonStructure(['access_token', 'refresh_token', 'client']);
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/client/token/refresh', ['refresh_token' => $other['refresh_token']])->assertUnauthorized();
        $this->postJson('/api/client/token/refresh', ['refresh_token' => $response->json('refresh_token')])->assertSuccessful();
    }
}

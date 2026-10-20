<?php

declare(strict_types=1);

namespace Domain\Client\Tests\Feature;

use Domain\Client\Models\Client;
use Domain\Client\Notifications\ClientCodeNotification;
use Domain\Identity\Actions\IssueSession;
use Domain\Identity\Data\IssueSessionData;
use Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class ClientAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparePassport();
        Notification::fake();
    }

    /** The code that went in the last e-mail to this client. */
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

    private function register(Client $client, string $code, string $password = 'minhasenha1')
    {
        return $this->postJson('/api/client/registration', ['email' => $client->email, 'code' => $code, 'password' => $password]);
    }

    public function test_a_client_who_booked_completes_the_sign_up_with_the_code_from_the_e_mail(): void
    {
        // Arrange
        $client = Client::factory()->create(['email' => 'ana@cliente.test']);
        $this->postJson('/api/client/codes', ['email' => 'ANA@cliente.test', 'purpose' => 'registration'])->assertSuccessful();

        // Act
        $response = $this->register($client, $this->lastCode($client));

        // Assert
        $response->assertSuccessful();
        $response->assertJsonPath('client.email', 'ana@cliente.test');
        $response->assertJsonPath('client.has_account', true);
        $this->assertNotEmpty($response->json('refresh_token'));
        $this->assertNotNull($client->fresh()->email_verified_at);
        $this->withToken($response->json('access_token'))->getJson('/api/client/me')->assertSuccessful()->assertJsonPath('id', $client->id);
    }

    public function test_the_answer_is_the_same_for_any_e_mail_and_codes_go_only_when_they_make_sense(): void
    {
        // Arrange
        $registered = Client::factory()->registered()->create();

        // Act
        $unknown = $this->postJson('/api/client/codes', ['email' => 'ninguem@cliente.test', 'purpose' => 'registration']);
        $alreadyRegistered = $this->postJson('/api/client/codes', ['email' => $registered->email, 'purpose' => 'registration']);

        // Assert
        $this->assertSame($unknown->json(), $alreadyRegistered->json());
        $unknown->assertSuccessful()->assertJsonStructure(['expires_in', 'resend_after']);
        Notification::assertNothingSent();
    }

    public function test_a_new_code_waits_a_few_seconds_and_replaces_the_old_one(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $this->postJson('/api/client/codes', ['email' => $client->email, 'purpose' => 'registration']);

        // Act
        $this->postJson('/api/client/codes', ['email' => $client->email, 'purpose' => 'registration']);
        $this->travel(31)->seconds();
        $this->postJson('/api/client/codes', ['email' => $client->email, 'purpose' => 'registration']);

        // Assert
        Notification::assertSentToTimes($client, ClientCodeNotification::class, 2);
    }

    public function test_a_wrong_code_tells_the_attempts_left_and_blocks_after_five(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $this->postJson('/api/client/codes', ['email' => $client->email, 'purpose' => 'registration']);
        $right = $this->lastCode($client);
        $wrong = $right === '000000' ? '111111' : '000000';

        // Act
        $first = $this->register($client, $wrong);
        foreach (range(1, 3) as $attempt) {
            $this->register($client, $wrong);
        }
        $fifth = $this->register($client, $wrong);
        $afterBlock = $this->register($client, $right);

        // Assert
        $first->assertUnprocessable()->assertJsonPath('code', 'INVALID_CODE')->assertJsonPath('data.attempts_left', 4);
        $fifth->assertStatus(429)->assertJsonPath('code', 'TOO_MANY_ATTEMPTS');
        $afterBlock->assertStatus(429);
        $this->assertFalse($client->fresh()->hasAccount());
    }

    public function test_a_code_works_once_and_expires(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $other = Client::factory()->create();
        $this->postJson('/api/client/codes', ['email' => $client->email, 'purpose' => 'registration']);
        $this->postJson('/api/client/codes', ['email' => $other->email, 'purpose' => 'registration']);
        $code = $this->lastCode($client);
        $otherCode = $this->lastCode($other);

        // Act
        $this->register($client, $code)->assertSuccessful();
        $reused = $this->register($client, $code);
        $this->travel(16)->minutes();
        $expired = $this->register($other, $otherCode);

        // Assert
        $reused->assertStatus(410)->assertJsonPath('code', 'CODE_EXPIRED');
        $expired->assertStatus(410);
    }

    public function test_signs_in_with_e_mail_and_password_only_after_the_sign_up(): void
    {
        // Arrange
        $registered = Client::factory()->registered()->create();
        $withoutPassword = Client::factory()->create();

        // Act
        $ok = $this->postJson('/api/client/login', ['email' => $registered->email, 'password' => 'segredo123']);
        $wrong = $this->postJson('/api/client/login', ['email' => $registered->email, 'password' => 'errada123']);
        $notRegistered = $this->postJson('/api/client/login', ['email' => $withoutPassword->email, 'password' => 'qualquer1']);

        // Assert
        $ok->assertSuccessful()->assertJsonPath('client.id', $registered->id);
        $wrong->assertUnauthorized()->assertJsonPath('code', 'INVALID_CREDENTIALS');
        $notRegistered->assertUnauthorized()->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_the_refresh_token_rotates_and_a_reused_one_ends_the_session(): void
    {
        // Arrange
        $client = Client::factory()->registered()->create();
        $first = $this->postJson('/api/client/login', ['email' => $client->email, 'password' => 'segredo123'])->json('refresh_token');

        // Act
        $second = $this->postJson('/api/client/token/refresh', ['refresh_token' => $first]);
        $reuse = $this->postJson('/api/client/token/refresh', ['refresh_token' => $first]);
        $afterReuse = $this->postJson('/api/client/token/refresh', ['refresh_token' => $second->json('refresh_token')]);

        // Assert
        $second->assertSuccessful();
        $reuse->assertUnauthorized()->assertJsonPath('code', 'INVALID_REFRESH_TOKEN');
        $afterReuse->assertUnauthorized();
    }

    public function test_forgot_password_sets_a_new_one_and_ends_the_other_sessions(): void
    {
        // Arrange
        $client = Client::factory()->registered()->create();
        $oldRefresh = $this->postJson('/api/client/login', ['email' => $client->email, 'password' => 'segredo123'])->json('refresh_token');
        $this->postJson('/api/client/codes', ['email' => $client->email, 'purpose' => 'password'])->assertSuccessful();

        // Act
        $response = $this->postJson('/api/client/password', ['email' => $client->email, 'code' => $this->lastCode($client), 'password' => 'novasenha9']);

        // Assert
        $response->assertSuccessful();
        $this->postJson('/api/client/login', ['email' => $client->email, 'password' => 'novasenha9'])->assertSuccessful();
        $this->postJson('/api/client/token/refresh', ['refresh_token' => $oldRefresh])->assertUnauthorized();
    }

    public function test_logout_ends_the_session(): void
    {
        // Arrange
        $client = Client::factory()->registered()->create();
        $session = $this->postJson('/api/client/login', ['email' => $client->email, 'password' => 'segredo123'])->json();

        // Act
        $this->withToken($session['access_token'])->postJson('/api/client/logout', ['refresh_token' => $session['refresh_token']])->assertNoContent();
        $this->app['auth']->forgetGuards();

        // Assert
        $this->withToken($session['access_token'])->getJson('/api/client/me')->assertUnauthorized();
        $this->postJson('/api/client/token/refresh', ['refresh_token' => $session['refresh_token']])->assertUnauthorized();
    }

    public function test_a_client_token_never_opens_a_barber_route_and_the_other_way_round(): void
    {
        // Arrange: a client and a barber with the same id.
        $barber = User::factory()->create();
        $client = Client::factory()->registered()->create(['id' => $barber->id]);
        $clientToken = $this->postJson('/api/client/login', ['email' => $client->email, 'password' => 'segredo123'])->json('access_token');
        $barberToken = app(IssueSession::class)->handle(new IssueSessionData(user_id: $barber->id))->access_token;

        // Act
        $clientOnBarber = $this->withToken($clientToken)->getJson('/api/settings');
        $this->app['auth']->forgetGuards();
        $barberOnClient = $this->withToken($barberToken)->getJson('/api/client/me');
        $this->app['auth']->forgetGuards();
        $clientOnClient = $this->withToken($clientToken)->getJson('/api/client/me');

        // Assert
        $clientOnBarber->assertUnauthorized();
        $barberOnClient->assertUnauthorized();
        $clientOnClient->assertSuccessful();
    }
}

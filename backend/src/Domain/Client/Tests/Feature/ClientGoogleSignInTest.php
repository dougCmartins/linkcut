<?php

declare(strict_types=1);

namespace Domain\Client\Tests\Feature;

use Domain\Client\Actions\ListClientsToInvite;
use Domain\Client\Data\ClientInviteBatchData;
use Domain\Client\Models\Client;
use Domain\Client\Support\ClientGoogleLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/** Client sign-in with Google: link or create the account, then a one-time code for the session. */
final class ClientGoogleSignInTest extends TestCase
{
    use RefreshDatabase;

    private const SPA = 'https://linkcut.test';

    private const NONCE = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private ?string $state = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparePassport();
        config(['app.frontend_url' => self::SPA]);
    }

    public function test_a_new_google_account_creates_a_client_without_a_password(): void
    {
        // Arrange
        $this->fakeGoogle($this->googleUser('google-1', 'ana@example.com', str_repeat('A', 80), true));
        $this->start('/conta/perfil');
        $callback = $this->googleCallback();
        $code = $this->codeOf($callback);

        // Act
        $started = $this->postJson('/api/client/auth/google/exchange', ['code' => $code, 'nonce' => self::NONCE]);
        $again = $this->postJson('/api/client/auth/google/exchange', ['code' => $code, 'nonce' => self::NONCE]);
        $this->start('/conta');
        $this->googleCallback();

        // Assert
        $client = Client::query()->where('email', 'ana@example.com')->firstOrFail();
        $this->assertSame('google-1', $client->google_id);
        $this->assertSame(str_repeat('A', 60), $client->name);
        $this->assertSame('', $client->phone);
        $this->assertNull($client->password);
        $this->assertNotNull($client->email_verified_at);
        $started->assertSuccessful()
            ->assertJsonPath('client.email', 'ana@example.com')
            ->assertJsonPath('client.has_account', false)
            ->assertJsonPath('client.google_linked', true)
            ->assertJsonMissingPath('client.google_id');
        $this->assertNotEmpty($started->json('access_token'));
        $again->assertStatus(422)->assertJsonPath('code', 'GOOGLE_CODE_INVALID');
        $this->assertSame(1, Client::query()->count());
    }

    public function test_an_existing_client_is_linked_by_e_mail_and_keeps_the_phone(): void
    {
        // Arrange
        $client = Client::factory()->registered()->create([
            'email' => 'ana@example.com',
            'phone' => '11912345678',
        ]);
        $this->fakeGoogle($this->googleUser('google-2', 'ANA@example.com', 'Ana Costa', true));
        $this->start();

        // Act
        $response = $this->exchange($this->googleCallback());

        // Assert
        $client->refresh();
        $response->assertSuccessful()->assertJsonPath('client.has_account', true)->assertJsonPath('client.google_linked', true);
        $this->assertSame('google-2', $client->google_id);
        $this->assertSame('11912345678', $client->phone);
        $this->assertTrue($client->hasAccount());
    }

    public function test_an_unverified_google_e_mail_is_refused(): void
    {
        // Arrange
        $this->fakeGoogle($this->googleUser('google-3', 'ana@example.com', 'Ana', false));
        $this->start();

        // Act
        $response = $this->googleCallback();

        // Assert
        $response->assertRedirect(self::SPA.'/conta/entrar?erro=google');
        $this->assertDatabaseCount('clients', 0);
    }

    public function test_an_e_mail_already_linked_to_another_google_account_is_not_taken_over(): void
    {
        // Arrange
        $client = Client::factory()->create([
            'email' => 'ana@example.com',
            'google_id' => 'google-old',
        ]);
        $this->fakeGoogle($this->googleUser('google-new', 'ana@example.com', 'Ana', true));
        $this->start();

        // Act
        $response = $this->googleCallback();

        // Assert
        $response->assertRedirect(self::SPA.'/conta/entrar?erro=google');
        $this->assertSame('google-old', $client->fresh()->google_id);
    }

    public function test_a_cancelled_google_return_goes_back_to_the_login(): void
    {
        // Arrange
        $this->fakeGoogle($this->googleUser('google-4', 'ana@example.com', 'Ana', true));
        $this->start();

        // Act
        $response = $this->get('/api/client/auth/google/callback?error=access_denied&state='.$this->state);

        // Assert
        $response->assertRedirect(self::SPA.'/conta/entrar?erro=google');
        $this->assertNull(Cache::get(ClientGoogleLogin::stateKey((string) $this->state)));
        $this->assertDatabaseCount('clients', 0);
    }

    public function test_the_code_expires_after_a_minute(): void
    {
        // Arrange
        $this->fakeGoogle($this->googleUser('google-5', 'ana@example.com', 'Ana', true));
        $this->start();
        $code = $this->codeOf($this->googleCallback());
        $this->travel(61)->seconds();

        // Act
        $response = $this->postJson('/api/client/auth/google/exchange', ['code' => $code, 'nonce' => self::NONCE]);

        // Assert
        $response->assertStatus(422)->assertJsonPath('code', 'GOOGLE_CODE_INVALID');
    }

    public function test_a_code_sent_to_another_browser_does_not_open_a_session(): void
    {
        // Arrange: someone signs in with their own Google and sends the code link to another person
        $this->fakeGoogle($this->googleUser('google-9', 'attacker@example.com', 'Attacker', true));
        $this->start();
        $code = $this->codeOf($this->googleCallback());

        // Act: the other browser never started a sign-in, so it has another nonce (or none)
        $otherBrowser = $this->postJson('/api/client/auth/google/exchange', ['code' => $code, 'nonce' => str_repeat('b', 40)]);
        $starter = $this->postJson('/api/client/auth/google/exchange', ['code' => $code, 'nonce' => self::NONCE]);
        $noNonce = $this->postJson('/api/client/auth/google/exchange', ['code' => $code]);

        // Assert: refused, and the code is gone after the first try
        $otherBrowser->assertStatus(422)->assertJsonPath('code', 'GOOGLE_CODE_INVALID');
        $starter->assertStatus(422)->assertJsonPath('code', 'GOOGLE_CODE_INVALID');
        $noNonce->assertUnprocessable();
    }

    public function test_a_google_only_client_gets_no_invitation_to_create_a_password(): void
    {
        // Arrange
        $this->fakeGoogle($this->googleUser('google-10', 'ana@example.com', 'Ana', true));
        $this->start();
        $this->exchange($this->googleCallback())->assertSuccessful();
        $this->travel((int) config('client.invite_after_hours') + 1)->hours();

        // Act
        $clients = app(ListClientsToInvite::class)
            ->handle(new ClientInviteBatchData(limit: 10));

        // Assert
        $this->assertCount(0, $clients);
    }

    public function test_an_external_return_path_becomes_the_client_home(): void
    {
        // Arrange
        $this->fakeGoogle($this->googleUser('google-6', 'ana@example.com', 'Ana', true));

        // Act
        foreach (['https://evil.test', '//evil.test', '/\\evil.test'] as $volta) {
            $this->get('/api/client/auth/google/redirect?nonce='.self::NONCE.'&volta='.urlencode($volta))->assertRedirect();
            $this->assertSame('/conta', Cache::get(ClientGoogleLogin::stateKey((string) $this->state))['volta'] ?? null);
        }
        $location = (string) $this->googleCallback()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        // Assert
        $this->assertStringStartsWith(self::SPA.'/conta/entrar/google?', $location);
        $this->assertSame('/conta', $query['volta'] ?? null);
        $this->assertStringNotContainsString('evil', $location);
    }

    public function test_the_export_says_the_account_is_linked_to_google_without_the_google_id(): void
    {
        // Arrange
        $this->fakeGoogle($this->googleUser('google-7', 'ana@example.com', 'Ana', true));
        $this->start();
        $token = $this->exchange($this->googleCallback())->json('access_token');

        // Act
        $response = $this->withToken($token)->get('/api/client/me/export', ['Accept' => 'application/json']);

        // Assert
        $response->assertOk()->assertJsonPath('account.google_linked', true);
        $this->assertArrayNotHasKey('google_id', $response->json('account'));
    }

    public function test_a_google_only_account_can_be_deleted_without_a_password(): void
    {
        // Arrange
        $client = Client::factory()->create([
            'google_id' => 'google-8',
            'email_verified_at' => now(),
        ]);
        Passport::actingAs($client, [], 'client');

        // Act
        $deleted = $this->deleteJson('/api/client/me', ['password' => '']);
        $withPassword = Client::factory()->registered()->create();
        Passport::actingAs($withPassword, [], 'client');
        $refused = $this->deleteJson('/api/client/me', ['password' => '']);

        // Assert
        $deleted->assertNoContent();
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $refused->assertUnprocessable();
        $this->assertDatabaseHas('clients', ['id' => $withPassword->id]);
    }

    private function fakeGoogle(SocialiteUser $user): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('with')->andReturnUsing(function (array $parameters) use ($provider): Provider {
            $this->state = is_string($parameters['state'] ?? null) ? $parameters['state'] : null;

            return $provider;
        });
        $provider->shouldReceive('redirect')->andReturn(new RedirectResponse('https://accounts.google.com/o/oauth2/v2/auth'));
        $provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function googleUser(string $id, string $email, string $name, bool $verified): SocialiteUser
    {
        return (new SocialiteUser)->setRaw([
            'sub' => $id,
            'email' => $email,
            'name' => $name,
            'email_verified' => $verified,
        ])->map([
            'id' => $id,
            'nickname' => null,
            'name' => $name,
            'email' => $email,
            'avatar' => null,
        ]);
    }

    private function start(string $volta = '/conta'): void
    {
        $this->get('/api/client/auth/google/redirect?nonce='.self::NONCE.'&volta='.urlencode($volta))
            ->assertRedirect('https://accounts.google.com/o/oauth2/v2/auth');
        $this->assertNotNull($this->state);
        $this->assertSame(ClientGoogleLogin::safeReturnPath($volta), Cache::get(ClientGoogleLogin::stateKey($this->state))['volta'] ?? null);
    }

    private function googleCallback(): TestResponse
    {
        return $this->get('/api/client/auth/google/callback?code=google-code&state='.$this->state);
    }

    private function codeOf(TestResponse $response): string
    {
        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith(self::SPA.'/conta/entrar/google?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertIsString($query['codigo'] ?? null);

        return $query['codigo'];
    }

    private function exchange(TestResponse $callback): TestResponse
    {
        return $this->postJson('/api/client/auth/google/exchange', ['code' => $this->codeOf($callback), 'nonce' => self::NONCE]);
    }
}

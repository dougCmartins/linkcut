<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Onboarding\Tests\Feature;

use Domain\Identity\Models\User;
use Domain\Identity\Notifications\AccountExistsNotification;
use Domain\Identity\Notifications\SignUpCodeNotification;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\Service;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Open sign-up of a barber: e-mail code → barbershop, owner and default settings → session. */
final class SignUpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparePassport();
        Notification::fake();
    }

    /** The code that went in the last sign-up e-mail to this address. */
    private function codeSentTo(string $email): string
    {
        $code = '';
        Notification::assertSentOnDemand(SignUpCodeNotification::class, function (SignUpCodeNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($email, &$code): bool {
            if ($notifiable->routeNotificationFor('mail') === $email) {
                $code = $notification->code;
            }

            return true;
        });

        return $code;
    }

    /** @return array<string, string> */
    private function payload(string $code, array $overrides = []): array
    {
        return [
            'name' => 'Marcos Lima',
            'email' => 'marcos@example.com',
            'password' => 'segredo123',
            'code' => $code,
            'barbershop_name' => 'Barbearia do Marcos',
            'slug' => 'barbearia-do-marcos',
            'accept_terms' => true,
            ...$overrides,
        ];
    }

    public function test_signs_up_with_the_e_mail_code_and_creates_the_barbershop_ready_for_the_wizard(): void
    {
        // Arrange
        $this->postJson('/api/sign-up/code', ['email' => 'Marcos@Example.com'])->assertCreated();
        $code = $this->codeSentTo('marcos@example.com');

        // Act
        $response = $this->postJson('/api/sign-up', $this->payload($code));

        // Assert
        $response->assertCreated()->assertJsonStructure(['access_token', 'refresh_token', 'user' => ['id', 'tenant_id']]);
        $user = User::query()->sole();
        $this->assertSame('marcos@example.com', $user->email);
        $this->assertSame('owner', $user->role->value);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertSame(config('legal.terms_version'), $user->terms_version);
        $tenant = Tenant::query()->sole();
        $this->assertSame('barbearia-do-marcos', $tenant->slug);
        $this->assertNull($tenant->onboarded_at);
        app(CurrentTenant::class)->run($tenant->id, function (): void {
            $this->assertSame('Barbearia do Marcos', ProfileSettings::query()->sole()->name);
            $this->assertSame(2, Service::query()->count());
        });
        $this->withToken($response->json('access_token'))->getJson('/api/tenant')
            ->assertOk()->assertJsonPath('slug', 'barbearia-do-marcos')->assertJsonPath('onboarded', false);
    }

    public function test_a_wrong_code_counts_attempts_and_creates_nothing(): void
    {
        // Arrange
        $this->postJson('/api/sign-up/code', ['email' => 'marcos@example.com']);
        $right = $this->codeSentTo('marcos@example.com');
        $wrong = $right === '000000' ? '111111' : '000000';

        // Act
        $response = $this->postJson('/api/sign-up', $this->payload($wrong));

        // Assert
        $response->assertUnprocessable()->assertJsonPath('code', 'INVALID_CODE')->assertJsonPath('data.attempts_left', 4);
        $this->assertSame(0, Tenant::query()->count());
        $this->assertSame(0, User::query()->count());
    }

    public function test_an_e_mail_with_an_account_gets_the_same_answer_but_a_sign_in_e_mail_and_no_code(): void
    {
        // Arrange
        $existing = User::factory()->create(['email' => 'rafael@linkcut.app']);

        // Act
        $known = $this->postJson('/api/sign-up/code', ['email' => 'rafael@linkcut.app']);
        $unknown = $this->postJson('/api/sign-up/code', ['email' => 'new@example.com']);
        $signUp = $this->postJson('/api/sign-up', $this->payload('123456', ['email' => $existing->email]));

        // Assert
        $this->assertSame($unknown->json(), $known->json());
        Notification::assertSentOnDemand(AccountExistsNotification::class, fn ($notification, $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routeNotificationFor('mail') === 'rafael@linkcut.app');
        Notification::assertSentOnDemandTimes(SignUpCodeNotification::class, 1);
        $signUp->assertStatus(410)->assertJsonPath('code', 'CODE_EXPIRED');
    }

    public function test_reserved_and_taken_links_are_refused_and_a_free_one_is_suggested(): void
    {
        // Arrange
        Tenant::factory()->create(['slug' => 'navalha']);
        Tenant::factory()->create(['slug' => 'navalha-2']);

        // Act
        $taken = $this->getJson('/api/sign-up/slug?slug=navalha');
        $reserved = $this->getJson('/api/sign-up/slug?slug=painel');
        $free = $this->getJson('/api/sign-up/slug?slug=nova-barbearia');
        $invalid = $this->getJson('/api/sign-up/slug?slug=Com%20Espaco');
        $this->postJson('/api/sign-up/code', ['email' => 'marcos@example.com']);
        $signUp = $this->postJson('/api/sign-up', $this->payload($this->codeSentTo('marcos@example.com'), ['slug' => 'conta']));

        // Assert
        $taken->assertOk()->assertJson(['available' => false, 'suggestion' => 'navalha-3']);
        $reserved->assertOk()->assertJson(['available' => false, 'suggestion' => 'painel-2']);
        $free->assertOk()->assertJson(['available' => true, 'suggestion' => 'nova-barbearia']);
        $invalid->assertUnprocessable()->assertJsonPath('errors.slug.0', 'regex');
        $signUp->assertConflict()->assertJsonPath('code', 'SLUG_UNAVAILABLE');
        $this->assertSame(0, User::query()->count());
    }

    public function test_the_code_works_once_and_asking_again_too_soon_sends_nothing_new(): void
    {
        // Arrange
        $this->postJson('/api/sign-up/code', ['email' => 'marcos@example.com']);
        $this->postJson('/api/sign-up/code', ['email' => 'marcos@example.com']);
        $code = $this->codeSentTo('marcos@example.com');
        $this->postJson('/api/sign-up', $this->payload($code))->assertCreated();

        // Act
        $again = $this->postJson('/api/sign-up', $this->payload($code, ['slug' => 'outra-barbearia']));

        // Assert
        Notification::assertSentOnDemandTimes(SignUpCodeNotification::class, 1);
        $again->assertStatus(410)->assertJsonPath('code', 'CODE_EXPIRED');
        $this->assertSame(1, Tenant::query()->count());
    }

    public function test_the_barber_finishes_the_wizard_once(): void
    {
        // Arrange
        $this->postJson('/api/sign-up/code', ['email' => 'marcos@example.com']);
        $token = $this->postJson('/api/sign-up', $this->payload($this->codeSentTo('marcos@example.com')))->json('access_token');

        // Act
        $response = $this->withToken($token)->postJson('/api/tenant/onboarding/complete');

        // Assert
        $response->assertSuccessful()->assertJsonPath('onboarded', true);
        $this->assertNotNull(Tenant::query()->sole()->onboarded_at);
    }

    public function test_the_terms_must_be_accepted(): void
    {
        // Arrange
        $this->postJson('/api/sign-up/code', ['email' => 'marcos@example.com'])->assertCreated();

        // Act
        $response = $this->postJson('/api/sign-up', $this->payload($this->codeSentTo('marcos@example.com'), ['accept_terms' => false]));

        // Assert
        $response->assertUnprocessable()->assertJsonValidationErrors('accept_terms');
        $this->assertSame(0, User::query()->count());
    }
}

<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Team\Tests\Feature;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Identity\Tests\Concerns\SignsIn;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffPermission;
use Domain\Team\Notifications\StaffInvitationNotification;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** The team of a unit: invitation, roles and what a barber may see (docs/decisoes/0001 P3). */
final class TeamTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;
    use SignsIn;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparePassport();
        $this->freezeMondayMorning('10:00');
        Notification::fake();
    }

    private function invite(User $owner, string $email = 'joao@example.com'): TestResponse
    {
        $this->actingAsOwner($owner);

        return $this->postJson('/api/team/staff', ['name' => 'João Barbeiro', 'email' => $email]);
    }

    /** The token of the last invitation e-mail sent to this address. */
    private function tokenSentTo(string $email): string
    {
        $token = '';
        Notification::assertSentTo(new AnonymousNotifiable, StaffInvitationNotification::class, function (StaffInvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($email, &$token): bool {
            if (($notifiable->routes['mail'] ?? null) !== $email) {
                return false;
            }
            parse_str((string) parse_url($notification->url, PHP_URL_QUERY), $query);
            $token = (string) $query['token'];

            return true;
        });

        return $token;
    }

    private function accept(string $token): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->postJson('/api/team/invitations/accept', ['token' => $token, 'password' => 'senha-forte-1', 'accept_terms' => true]);
    }

    /** A barber who accepted the invitation, with their own session token. */
    private function barberOf(User $owner): array
    {
        $this->invite($owner)->assertCreated();
        $session = $this->accept($this->tokenSentTo('joao@example.com'))->assertCreated()->json();

        return [User::query()->where('email', 'joao@example.com')->sole(), $session['access_token']];
    }

    public function test_the_owner_invites_a_barber_who_joins_the_unit_with_their_own_login(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');

        // Act
        $invited = $this->invite($owner);
        $accepted = $this->accept($this->tokenSentTo('joao@example.com'));

        // Assert
        $invited->assertCreated()->assertJsonPath('status', 'invited')->assertJsonPath('permissions.see_prices', false);
        $accepted->assertCreated()->assertJsonStructure(['access_token', 'refresh_token']);
        $barber = User::query()->where('email', 'joao@example.com')->sole();
        $this->assertSame(UserRole::Barber, $barber->role);
        $this->assertSame($owner->tenant_id, $barber->tenant_id);
        $this->assertSame($owner->organization_id, $barber->organization_id);
        $staff = app(CurrentTenant::class)->run($owner->tenant_id, fn () => Staff::query()->sole());
        $this->assertSame($barber->id, $staff->user_id);
        $this->actingAsOwner($owner);
        $this->getJson('/api/team/staff')->assertOk()->assertJsonPath('0.status', 'active');
    }

    public function test_the_invitation_works_once_and_expires(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $this->invite($owner)->assertCreated();
        $token = $this->tokenSentTo('joao@example.com');
        $this->invite($owner, 'maria@example.com')->assertCreated();
        $lateToken = $this->tokenSentTo('maria@example.com');
        $this->accept($token)->assertCreated();

        // Act
        $again = $this->accept($token);
        $this->travel(8)->days();
        $expired = $this->accept($lateToken);
        $unknown = $this->accept(str_repeat('x', 48));

        // Assert
        $again->assertUnprocessable()->assertJsonPath('code', 'INVITATION_INVALID');
        $expired->assertUnprocessable()->assertJsonPath('code', 'INVITATION_INVALID');
        $unknown->assertUnprocessable()->assertJsonPath('code', 'INVITATION_INVALID');
        $this->assertFalse(User::query()->where('email', 'maria@example.com')->exists());
    }

    public function test_a_barber_without_see_prices_gets_no_value_from_the_api_and_with_it_does(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $this->bookingRequest($owner, '2026-10-20', '10:00', BookingStatus::Confirmed);
        [$barber, $token] = $this->barberOf($owner);

        // Act
        $this->app['auth']->forgetGuards();
        $without = $this->withToken($token)->getJson('/api/schedule/requests?status=confirmed');
        app(CurrentTenant::class)->run($owner->tenant_id, fn () => StaffPermission::query()->update(['see_prices' => true]));
        $this->app['auth']->forgetGuards();
        $with = $this->withToken($token)->getJson('/api/schedule/requests?status=confirmed');

        // Assert
        $without->assertOk();
        $this->assertNotEmpty($without->json());
        $this->assertStringNotContainsString('_in_cents', (string) $without->getContent());
        $with->assertOk();
        $this->assertStringContainsString('price_in_cents', (string) $with->getContent());
    }

    public function test_a_barber_of_the_team_cannot_open_what_only_the_owner_sees(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        [, $token] = $this->barberOf($owner);
        $this->app['auth']->forgetGuards();

        // Act
        $answers = [
            $this->withToken($token)->getJson('/api/finance/month-to-date'),
            $this->withToken($token)->getJson('/api/settings'),
            $this->withToken($token)->getJson('/api/products'),
            $this->withToken($token)->getJson('/api/team/staff'),
            $this->withToken($token)->getJson('/api/account/export'),
            $this->withToken($token)->postJson('/api/team/staff', ['name' => 'Outro', 'email' => 'outro@example.com']),
        ];

        // Assert
        foreach ($answers as $answer) {
            $answer->assertForbidden()->assertJsonPath('code', 'OWNER_ONLY');
        }
        $this->withToken($token)->getJson('/api/me')->assertOk();
    }

    public function test_turning_a_barber_off_ends_their_sessions_and_they_cannot_sign_in_again(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        [$barber, $token] = $this->barberOf($owner);
        $staffId = app(CurrentTenant::class)->run($owner->tenant_id, fn () => Staff::query()->value('id'));
        $this->actingAsOwner($owner);

        // Act
        $off = $this->putJson("/api/team/staff/{$staffId}", ['active' => false, 'permissions' => ['see_prices' => false, 'see_own_commission' => false, 'see_unit_agenda' => false]]);
        $this->app['auth']->forgetGuards();
        $oldSession = $this->withToken($token)->getJson('/api/me');
        $challengeId = $this->startLogin($barber->fresh(), 'senha-forte-1');
        $this->sendCode($challengeId)->assertCreated();
        $signIn = $this->postJson('/api/two-factor/verify', ['challenge_id' => $challengeId, 'code' => $this->lastCodeSentTo($barber)]);

        // Assert
        $off->assertOk()->assertJsonPath('status', 'inactive');
        $oldSession->assertUnauthorized();
        $signIn->assertForbidden()->assertJsonPath('code', 'STAFF_INACTIVE');
    }

    public function test_the_team_of_one_unit_is_invisible_to_another(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $other = $this->barbershop('outra');
        $this->invite($owner)->assertCreated();
        $staffId = app(CurrentTenant::class)->run($owner->tenant_id, fn () => Staff::query()->value('id'));
        $this->actingAsOwner($other);

        // Act
        $list = $this->getJson('/api/team/staff');
        $update = $this->putJson("/api/team/staff/{$staffId}", ['active' => false, 'permissions' => ['see_prices' => true, 'see_own_commission' => false, 'see_unit_agenda' => false]]);

        // Assert
        $list->assertOk()->assertJsonCount(0);
        $update->assertNotFound()->assertJsonPath('code', 'STAFF_NOT_FOUND');
    }

    public function test_the_owner_cannot_turn_themselves_off_nor_invite_the_same_e_mail_twice(): void
    {
        // Arrange
        $owner = $this->barbershop('rafael');
        $ownStaff = app(CurrentTenant::class)->run($owner->tenant_id, fn () => Staff::query()->create(['user_id' => $owner->id, 'name' => $owner->name, 'email' => $owner->email]));
        $this->invite($owner)->assertCreated();

        // Act
        $self = $this->putJson("/api/team/staff/{$ownStaff->id}", ['active' => false, 'permissions' => ['see_prices' => false, 'see_own_commission' => false, 'see_unit_agenda' => false]]);
        $twice = $this->invite($owner, 'JOAO@example.com');

        // Assert
        $self->assertUnprocessable()->assertJsonPath('code', 'CANNOT_DEACTIVATE_SELF');
        $twice->assertStatus(409)->assertJsonPath('code', 'STAFF_EMAIL_TAKEN');
    }
}

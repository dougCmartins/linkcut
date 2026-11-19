<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Team\Actions;

use Domain\Identity\Actions\CreateBarber;
use Domain\Identity\Actions\IssueSession;
use Domain\Identity\Data\IssueSessionData;
use Domain\Identity\Data\NewBarberData;
use Domain\Identity\Data\SessionData;
use Domain\Identity\Data\VerifiedUserData;
use Domain\Orchestrator\Team\Data\AcceptInvitationData;
use Domain\Team\Actions\ConsumeStaffInvitation;
use Domain\Team\Actions\LinkStaffUser;
use Domain\Team\Data\InvitationTokenData;
use Domain\Team\Data\StaffUserData;
use Domain\Tenant\Actions\EnsureTenantIsActive;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\TenantIdData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * A barber accepts the team invitation, across domains (docs/decisoes/0001 P3): in one transaction the link is used
 * once (Team), the unit must be active (Tenant), the barber's login is created in that unit (Identity, role barber)
 * and tied to their place in the team (Team) → a session (Identity). The e-mail was proven by the link.
 */
final class AcceptStaffInvitation
{
    public function __construct(
        private readonly ConsumeStaffInvitation $consumeInvitation,
        private readonly EnsureTenantIsActive $ensureTenantIsActive,
        private readonly CreateBarber $createBarber,
        private readonly LinkStaffUser $linkStaffUser,
        private readonly CurrentTenant $currentTenant,
        private readonly IssueSession $issueSession,
    ) {}

    public function handle(AcceptInvitationData $data): SessionData
    {
        $barber = DB::transaction(function () use ($data): VerifiedUserData {
            $invitation = $this->consumeInvitation->handle(new InvitationTokenData(token: $data->token));
            $unit = $this->ensureTenantIsActive->handle(new TenantIdData(tenant_id: $invitation->tenant_id));
            $barber = $this->createBarber->handle(new NewBarberData(
                tenant_id: $unit->id,
                organization_id: $unit->organization_id,
                name: $invitation->name,
                email: $invitation->email,
                password: $data->password,
                terms_version: (string) config('legal.terms_version'),
            ));
            $this->currentTenant->run($unit->id, fn () => $this->linkStaffUser->handle(new StaffUserData(staff_id: $invitation->staff_id, user_id: $barber->user_id)));

            return $barber;
        });
        Log::info('team_invitation_accepted', ['user_id' => $barber->user_id, 'tenant_id' => $barber->tenant_id]);

        return $this->issueSession->handle(new IssueSessionData(user_id: $barber->user_id));
    }
}

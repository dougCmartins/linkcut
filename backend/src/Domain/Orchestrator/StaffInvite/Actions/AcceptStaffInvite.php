<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffInvite\Actions;

use Domain\Identity\Actions\CreateBarber;
use Domain\Identity\Actions\IssueSession;
use Domain\Identity\Data\IssueSessionData;
use Domain\Identity\Data\NewBarberData;
use Domain\Identity\Data\SessionData;
use Domain\Team\Actions\ConsumeStaffInvite;
use Domain\Team\Actions\LinkStaffUser;
use Domain\Team\Data\AcceptStaffInviteData;
use Domain\Team\Data\StaffIdData;
use Domain\Tenant\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Public flow: consume invite → create barber user → link staff → session. */
final class AcceptStaffInvite
{
    public function __construct(
        private readonly ConsumeStaffInvite $consumeInvite,
        private readonly CreateBarber $createBarber,
        private readonly LinkStaffUser $linkStaffUser,
        private readonly IssueSession $issueSession,
    ) {}

    public function handle(string $token, AcceptStaffInviteData $data): SessionData
    {
        $session = DB::transaction(function () use ($token, $data): SessionData {
            $invite = $this->consumeInvite->handle($token);
            $organizationId = (int) Tenant::query()->whereKey($invite->tenant_id)->value('organization_id');

            $barber = $this->createBarber->handle(new NewBarberData(
                tenant_id: $invite->tenant_id,
                organization_id: $organizationId,
                name: $data->name,
                email: $invite->email,
                password: $data->password,
                terms_version: (string) config('legal.terms_version'),
            ));

            $this->linkStaffUser->handle(
                new StaffIdData(id: $invite->staff_id),
                $barber->user_id,
                trim($data->name),
            );

            return $this->issueSession->handle(new IssueSessionData(user_id: $barber->user_id));
        });

        Log::info('staff_invite_accepted', ['user_id' => $session->user->id]);

        return $session;
    }
}

<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffInvite\Actions;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Notification\Actions\SendStaffInvite;
use Domain\Notification\Data\StaffInviteMailData;
use Domain\Team\Actions\CreateStaffInvite;
use Domain\Team\Data\CreateStaffInviteData;
use Domain\Team\Data\InviteStaffData;
use Domain\Team\Data\StaffData;
use Domain\Team\Exceptions\OwnerRequiredException;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Models\Tenant;
use Illuminate\Support\Facades\Auth;

/** Owner invites a barber: Team creates the row + invite; Notification sends the e-mail. */
final class InviteStaff
{
    public function __construct(
        private readonly CreateStaffInvite $createInvite,
        private readonly SendStaffInvite $sendInvite,
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(InviteStaffData $data): StaffData
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->role !== UserRole::Owner) {
            throw new OwnerRequiredException;
        }

        $created = $this->createInvite->handle(new CreateStaffInviteData(
            name: $data->name,
            email: $data->email,
            created_by: $user->id,
        ));

        $unitName = (string) Tenant::query()->whereKey($this->currentTenant->id())->value('name');
        $this->sendInvite->handle(new StaffInviteMailData(
            barber_name: $created->staff->name,
            barber_email: $created->email,
            unit_name: $unitName !== '' ? $unitName : 'linkcut',
            invite_url: $created->invite_url,
        ));

        return $created->staff;
    }
}

<?php

declare(strict_types=1);

namespace Domain\Team\Actions;

use Domain\Team\Data\NewStaffData;
use Domain\Team\Data\StaffActorData;
use Domain\Team\Data\StaffData;
use Domain\Team\Exceptions\StaffEmailTakenException;
use Domain\Team\Models\Staff;
use Domain\Team\Models\StaffPermission;
use Domain\Team\Support\StaffDataMapper;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** "Adicionar barbeiro": the barber joins the current unit (everything off) and gets the invitation by e-mail. */
final class InviteStaff
{
    public function __construct(private readonly SendStaffInvitation $sendInvitation) {}

    public function handle(StaffActorData $actor, NewStaffData $data): StaffData
    {
        $email = mb_strtolower(trim($data->email));
        if (Staff::query()->where('email', $email)->exists()) {
            throw new StaffEmailTakenException;
        }
        try {
            $staff = DB::transaction(function () use ($data, $email): Staff {
                $staff = Staff::query()->create(['name' => trim($data->name), 'email' => $email, 'active' => true]);
                StaffPermission::query()->create(['staff_id' => $staff->id]);

                return $staff;
            });
        } catch (UniqueConstraintViolationException) {
            throw new StaffEmailTakenException;
        }
        $this->sendInvitation->handle($staff);
        Log::info('team_staff_invited', ['staff_id' => $staff->id]);

        return StaffDataMapper::toData($staff->load('permissions'), $actor->user_id);
    }
}

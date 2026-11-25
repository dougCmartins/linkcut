<?php

declare(strict_types=1);

namespace Domain\Orchestrator\StaffInvite\Controllers;

use Domain\Orchestrator\StaffInvite\Actions\AcceptStaffInvite;
use Domain\Team\Actions\PreviewStaffInvite;
use Domain\Team\Data\AcceptStaffInviteData;
use Domain\Team\Data\StaffInvitePreviewData;
use Illuminate\Http\JsonResponse;

final class PublicStaffInviteController
{
    public function show(string $token, PreviewStaffInvite $action): StaffInvitePreviewData
    {
        return $action->handle($token);
    }

    public function accept(string $token, AcceptStaffInviteData $data, AcceptStaffInvite $action): JsonResponse
    {
        return response()->json($action->handle($token, $data)->toArray(), 201);
    }
}

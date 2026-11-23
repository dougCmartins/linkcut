<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BrandReport\Support;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Orchestrator\BrandReport\Exceptions\BrandReportForbiddenException;

/** Only the owner reads the brand reports. The organization always comes from the user, never from the request. */
final class AssertBrandOwner
{
    /** @return int the organization id of the user */
    public static function handle(?User $user): int
    {
        if ($user === null || $user->role !== UserRole::Owner || ! $user->organization_id) {
            throw new BrandReportForbiddenException;
        }

        return (int) $user->organization_id;
    }
}

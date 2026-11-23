<?php

declare(strict_types=1);

namespace Domain\Finance\Support;

use Domain\Finance\Exceptions\OwnerRequiredException;
use Illuminate\Support\Facades\Auth;

/** Owner gate without importing Identity (ArchitectureTest). Role value is the backed enum string. */
final class AssertOwner
{
    public static function handle(): void
    {
        $user = Auth::user();
        $role = $user?->role;
        $value = is_object($role) && property_exists($role, 'value') ? $role->value : (string) $role;
        if ($user === null || $value !== 'owner') {
            throw new OwnerRequiredException;
        }
    }
}

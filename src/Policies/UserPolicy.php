<?php

namespace DoITs\EasyAuth\Policies;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Models\Tenant;

class UserPolicy
{
    /**
     * Determine whether the user can delete their own account.
     *
     * Administrators are excluded for the same reason TenantPolicy::leave
     * excludes them from leaving: stepping down is an abdication of
     * responsibility that must be carried out by another administrator,
     * not a self-service action. Unlike the locked-out recovery flow in
     * Auth\AccountDeletionController, this path assumes the user can
     * still log in and step down first, so blocking here is not a dead end.
     */
    public function delete(EasyAuthUser $user, EasyAuthUser $targetUser): bool
    {
        if (! $user->is($targetUser)) {
            return false;
        }

        return ! $targetUser->tenants()
            ->wherePivot('role', Tenant::ADMIN_ROLE)
            ->exists();
    }
}

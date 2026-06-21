<?php

namespace DoITs\EasyAuth\Policies;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Models\Tenant;

class TenantPolicy
{
    /**
     * Determine whether the user can view the tenant's member list.
     */
    public function viewAnyMember(EasyAuthUser $user, Tenant $tenant): bool
    {
        return $tenant->isAdministeredBy($user);
    }

    /**
     * Determine whether the user can change a member's role within the tenant.
     */
    public function updateMember(EasyAuthUser $user, Tenant $tenant, EasyAuthUser $targetUser): bool
    {
        if ($user->is($targetUser)) {
            return false;
        }

        return $tenant->isAdministeredBy($user);
    }

    /**
     * Determine whether the user can remove a member from the tenant.
     */
    public function removeMember(EasyAuthUser $user, Tenant $tenant, EasyAuthUser $targetUser): bool
    {
        if ($user->is($targetUser)) {
            return false;
        }

        return $tenant->isAdministeredBy($user);
    }

    /**
     * Determine whether the user can leave the tenant on their own.
     *
     * Administrators are excluded: stepping down is an abdication of
     * responsibility that must be carried out by another administrator
     * (via removeMember), not a self-service action.
     */
    public function leave(EasyAuthUser $user, Tenant $tenant): bool
    {
        if ($tenant->isAdministeredBy($user)) {
            return false;
        }

        return $tenant->hasMember($user);
    }

    /**
     * Determine whether the user can update the tenant's settings.
     */
    public function update(EasyAuthUser $user, Tenant $tenant): bool
    {
        return $tenant->isAdministeredBy($user);
    }

    /**
     * Determine whether the user can switch their current tenant to this tenant.
     */
    public function switch(EasyAuthUser $user, Tenant $tenant): bool
    {
        return $tenant->hasMember($user);
    }
}

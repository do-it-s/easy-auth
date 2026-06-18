<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    /**
     * Determine whether the user can view the tenant's member list.
     */
    public function viewAnyMember(User $user, Tenant $tenant): bool
    {
        return $tenant->isAdministeredBy($user);
    }

    /**
     * Determine whether the user can change a member's role within the tenant.
     */
    public function updateMember(User $user, Tenant $tenant, User $targetUser): bool
    {
        if ($user->is($targetUser)) {
            return false;
        }

        return $tenant->isAdministeredBy($user);
    }

    /**
     * Determine whether the user can remove a member from the tenant.
     */
    public function removeMember(User $user, Tenant $tenant, User $targetUser): bool
    {
        if ($user->is($targetUser)) {
            return false;
        }

        return $tenant->isAdministeredBy($user);
    }

    /**
     * Determine whether the user can leave the tenant on their own.
     */
    public function leave(User $user, Tenant $tenant): bool
    {
        return $tenant->hasMember($user);
    }

    /**
     * Determine whether the user can update the tenant's settings.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $tenant->isAdministeredBy($user);
    }

    /**
     * Determine whether the user can switch their current tenant to this tenant.
     */
    public function switch(User $user, Tenant $tenant): bool
    {
        return $tenant->hasMember($user);
    }
}

<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;

class InvitationPolicy
{
    /**
     * Determine whether the user can view the tenant's invitations.
     */
    public function viewAny(User $user, Tenant $tenant): bool
    {
        return $tenant->isAdministeredBy($user);
    }

    /**
     * Determine whether the user can create invitations for the tenant.
     */
    public function create(User $user, Tenant $tenant): bool
    {
        return $tenant->isAdministeredBy($user)
            || ($tenant->member_invites_enabled && $tenant->hasMember($user));
    }

    /**
     * Determine whether the user can revoke the invitation.
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        return ! $invitation->is_backup_code
            && $invitation->tenant->isAdministeredBy($user);
    }
}

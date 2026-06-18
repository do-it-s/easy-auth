<?php

namespace DoITs\EasyAuth\Policies;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Models\Invitation;
use DoITs\EasyAuth\Models\Tenant;

class InvitationPolicy
{
    /**
     * Determine whether the user can view the tenant's invitations.
     */
    public function viewAny(EasyAuthUser $user, Tenant $tenant): bool
    {
        return $tenant->isAdministeredBy($user);
    }

    /**
     * Determine whether the user can create invitations for the tenant.
     */
    public function create(EasyAuthUser $user, Tenant $tenant): bool
    {
        return $tenant->isAdministeredBy($user)
            || ($tenant->member_invites_enabled && $tenant->hasMember($user));
    }

    /**
     * Determine whether the user can revoke the invitation.
     */
    public function delete(EasyAuthUser $user, Invitation $invitation): bool
    {
        return ! $invitation->is_backup_code
            && $invitation->tenant->isAdministeredBy($user);
    }
}

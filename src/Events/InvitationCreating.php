<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by InvitationController::store() immediately before the
 * Invitation is created, since that call only fills the hardcoded
 * role/token/label/expires_at/max_uses/created_by fields and an app adding
 * its own invitation fields needs a point to persist them alongside it.
 * There is no $invitation yet at this point (the row doesn't exist), so
 * listeners that need one should use InvitationCreated instead.
 */
class InvitationCreating
{
    use Dispatchable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly EasyAuthUser $user,
        public readonly array $validated,
    ) {}
}

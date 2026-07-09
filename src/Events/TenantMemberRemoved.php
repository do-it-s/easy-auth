<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched immediately after a member is detached from a tenant, by
 * TenantMemberController::destroy() and TenantLeaveController::destroy()
 * alike. See TenantMemberRemoving for why both share this event.
 */
class TenantMemberRemoved
{
    use Dispatchable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly EasyAuthUser $member,
    ) {}
}

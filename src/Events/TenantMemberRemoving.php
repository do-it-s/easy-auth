<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched immediately before a member is detached from a tenant, by
 * TenantMemberController::destroy() (an admin removing someone) and
 * TenantLeaveController::destroy() (a member removing themselves) alike —
 * both represent the same "a member is about to be removed from a Tenant"
 * event, just reached via different routes (see 00-plan.md's note that
 * leaving is treated as a self-service removal for eventing purposes).
 */
class TenantMemberRemoving
{
    use Dispatchable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly EasyAuthUser $member,
    ) {}
}

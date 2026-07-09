<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by TenantMemberController::update() immediately after the
 * pivot role is changed.
 */
class TenantMemberRoleUpdated
{
    use Dispatchable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly EasyAuthUser $member,
        public readonly string $role,
    ) {}
}

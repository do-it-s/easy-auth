<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by TenantController::store() after the tenant has been created
 * and the creating user attached as its first administrator.
 */
class TenantCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly EasyAuthUser $user,
    ) {}
}

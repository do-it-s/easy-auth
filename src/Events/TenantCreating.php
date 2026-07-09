<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by TenantController::store() immediately before Tenant::create(),
 * since that call only fills the hardcoded 'name' field and an app adding its
 * own tenant fields needs a point to persist them alongside it. There is no
 * $tenant yet at this point (the row doesn't exist), so listeners that need
 * one should use TenantCreated instead.
 */
class TenantCreating
{
    use Dispatchable;

    public function __construct(
        public readonly EasyAuthUser $user,
        public readonly array $validated,
    ) {}
}

<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by TenantController::update() immediately after $tenant->update().
 */
class TenantUpdated
{
    use Dispatchable;

    public function __construct(
        public readonly Tenant $tenant,
    ) {}
}

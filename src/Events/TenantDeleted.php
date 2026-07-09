<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by TenantController::destroy() immediately after $tenant->delete().
 */
class TenantDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly Tenant $tenant,
    ) {}
}

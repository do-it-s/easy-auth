<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by TenantController::destroy() immediately before $tenant->delete().
 */
class TenantDeleting
{
    use Dispatchable;

    public function __construct(
        public readonly Tenant $tenant,
    ) {}
}

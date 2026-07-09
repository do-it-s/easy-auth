<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by BackupCodeController::store() immediately before a new
 * backup-code Invitation is created (any previous usable one has already
 * been invalidated by this point).
 */
class BackupCodeIssuing
{
    use Dispatchable;

    public function __construct(
        public readonly Tenant $tenant,
    ) {}
}

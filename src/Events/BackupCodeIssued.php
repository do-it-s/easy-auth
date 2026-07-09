<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Models\Invitation;
use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by BackupCodeController::store() immediately after the new
 * backup-code Invitation is created.
 */
class BackupCodeIssued
{
    use Dispatchable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly Invitation $invitation,
    ) {}
}

<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Models\Invitation;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by InvitationController::store() immediately after the new
 * Invitation is created.
 */
class InvitationCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Invitation $invitation,
    ) {}
}

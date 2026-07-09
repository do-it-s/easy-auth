<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Models\Invitation;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by InvitationController::destroy() immediately after the
 * invitation is marked revoked.
 */
class InvitationRevoked
{
    use Dispatchable;

    public function __construct(
        public readonly Invitation $invitation,
    ) {}
}

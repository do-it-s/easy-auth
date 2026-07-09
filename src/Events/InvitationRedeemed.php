<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Models\Invitation;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by InvitationRedemptionController::redeem() immediately after
 * Invitation::redeemFor(). Also fires for backup-code redemptions, since
 * they share this same controller action.
 */
class InvitationRedeemed
{
    use Dispatchable;

    public function __construct(
        public readonly Invitation $invitation,
        public readonly EasyAuthUser $user,
    ) {}
}

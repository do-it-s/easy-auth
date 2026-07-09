<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Models\Invitation;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by InvitationRedemptionController::redeem() immediately before
 * Invitation::redeemFor(), which hardcodes the tenant pivot's role and
 * last_accessed_at (and, for backup codes, used_at/redeemed_by) — an app
 * wanting to react to or extend a redemption needs a point before those
 * writes happen. Also fires for backup-code redemptions, since they share
 * this same controller action.
 */
class InvitationRedeeming
{
    use Dispatchable;

    public function __construct(
        public readonly Invitation $invitation,
        public readonly EasyAuthUser $user,
    ) {}
}

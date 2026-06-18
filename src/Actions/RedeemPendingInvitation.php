<?php

namespace DoITs\EasyAuth\Actions;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Models\Invitation;
use Illuminate\Http\Request;

class RedeemPendingInvitation
{
    /**
     * Add the given user to the tenant referenced by a pending invitation
     * stored in the session, if one is present and still usable.
     *
     * This is a no-op when no invitation is pending, which is the normal
     * case for a user registering to become a tenant owner. Returns the
     * redeemed invitation, if any, so callers can react to it (e.g. an
     * administrator backup code being consumed).
     */
    public function __invoke(Request $request, EasyAuthUser $user): ?Invitation
    {
        $token = $request->session()->pull('pending_invitation_token');

        if (! $token) {
            return null;
        }

        $invitation = Invitation::where('token', Invitation::hashToken($token))->first();

        if ($invitation && $invitation->isUsable()) {
            $invitation->redeemFor($user);

            return $invitation;
        }

        return null;
    }
}

<?php

namespace App\Actions;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;

class RedeemPendingInvitation
{
    /**
     * Add the given user to the tenant referenced by a pending invitation
     * stored in the session, if one is present and still usable.
     *
     * This is a no-op when no invitation is pending, which is the normal
     * case for a user registering to become a tenant owner.
     */
    public function __invoke(Request $request, User $user): void
    {
        $token = $request->session()->pull('pending_invitation_token');

        if (! $token) {
            return;
        }

        $invitation = Invitation::where('token', Invitation::hashToken($token))->first();

        if ($invitation && $invitation->isUsable()) {
            $invitation->redeemFor($user);
        }
    }
}

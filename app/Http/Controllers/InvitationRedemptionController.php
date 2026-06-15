<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvitationRedemptionController extends Controller
{
    /**
     * Show the invitation, prompting the visitor to register or join.
     */
    public function show(Request $request, string $token): View|RedirectResponse
    {
        $invitation = Invitation::where('token', Invitation::hashToken($token))->first();

        if (! $invitation || ! $invitation->isUsable()) {
            return view('invitations.show', [
                'status' => 'invalid',
                'invitation' => null,
            ]);
        }

        $user = $request->user();

        if (! $user) {
            $request->session()->put('pending_invitation_token', $token);

            return redirect()->route('profile.create');
        }

        if (! $invitation->canBeRedeemedBy($user)) {
            return view('invitations.show', [
                'status' => 'already_admin',
                'invitation' => $invitation,
            ]);
        }

        return view('invitations.show', [
            'status' => 'confirm',
            'invitation' => $invitation,
            'token' => $token,
            'alreadyMember' => $invitation->tenant->hasMember($user),
            'isPromotion' => $invitation->role === Tenant::ADMIN_ROLE && ! $invitation->tenant->isAdministeredBy($user),
        ]);
    }

    /**
     * Redeem the invitation for the authenticated user.
     */
    public function redeem(Request $request, string $token): RedirectResponse|View
    {
        $invitation = Invitation::where('token', Invitation::hashToken($token))->first();

        if (! $invitation || ! $invitation->isUsable()) {
            return view('invitations.show', [
                'status' => 'invalid',
                'invitation' => null,
            ]);
        }

        $user = $request->user();

        if (! $invitation->canBeRedeemedBy($user)) {
            return view('invitations.show', [
                'status' => 'already_admin',
                'invitation' => $invitation,
            ]);
        }

        $invitation->redeemFor($user);

        if ($invitation->is_backup_code) {
            return redirect()->route('tenants.backup-code.show', $invitation->tenant);
        }

        return redirect()->route('home');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
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

        if ($invitation->tenant->hasMember($user)) {
            return view('invitations.show', [
                'status' => 'already_member',
                'invitation' => $invitation,
            ]);
        }

        return view('invitations.show', [
            'status' => 'confirm',
            'invitation' => $invitation,
            'token' => $token,
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

        if ($invitation->tenant->hasMember($user)) {
            return view('invitations.show', [
                'status' => 'already_member',
                'invitation' => $invitation,
            ]);
        }

        $invitation->redeemFor($user);

        return redirect()->route('home');
    }
}

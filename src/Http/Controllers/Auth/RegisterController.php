<?php

namespace DoITs\EasyAuth\Http\Controllers\Auth;

use DoITs\EasyAuth\Actions\RedeemPendingInvitation;
use DoITs\EasyAuth\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * Create a new user with an email and password, for devices where
     * passkeys are not available.
     */
    public function store(Request $request, RedeemPendingInvitation $redeemPendingInvitation): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], trans('easy-auth::validation'));

        $userModel = config('auth.providers.users.model');

        $user = $userModel::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $device = $user->device()->create(['uuid' => (string) Str::uuid()]);

        Auth::login($user);

        $invitation = $redeemPendingInvitation($request, $user);

        $redirectTo = $invitation?->is_backup_code
            ? route('tenants.backup-code.show', $invitation->tenant)
            : route('home');

        // An invitation was just redeemed for a specific tenant, which is a
        // more definitive destination than whatever the session's stale
        // url.intended happens to hold from an unrelated earlier visit (e.g.
        // a guest hitting a profile.complete-gated route days before using
        // this invitation link). Only fall back to intended() when there
        // was no invitation in play.
        $redirect = $invitation ? redirect($redirectTo) : redirect()->intended($redirectTo);

        return response()->json([
            'redirect' => $redirect->getTargetUrl(),
            'device_uuid' => $device->uuid,
        ]);
    }
}

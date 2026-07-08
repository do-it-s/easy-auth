<?php

namespace DoITs\EasyAuth\Http\Controllers;

use DoITs\EasyAuth\Actions\RedeemPendingInvitation;
use DoITs\EasyAuth\Actions\RejectSyncedPasskey;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Contracts\PasskeyRegistrationResponse;
use Laravel\Passkeys\Http\Requests\PasskeyRegistrationRequest;
use Laravel\Passkeys\Support\WebAuthn;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    /**
     * Show the registration page for a not-yet-created user.
     */
    public function create(Request $request): View
    {
        return view('easy-auth::profile.create', [
            'hasPendingInvitation' => $request->session()->has('pending_invitation_token'),
        ]);
    }

    /**
     * Generate passkey registration options for a not-yet-created user.
     */
    public function passkeyOptions(Request $request, GenerateRegistrationOptions $generate): JsonResponse
    {
        $userModel = config('auth.providers.users.model');

        // The account doesn't exist yet, so name/email/id are all still
        // unknown to PasskeyAuthenticatable. Without the name the profile
        // creation form already collected, WebAuthn's user.name/displayName
        // would be empty, which has been observed to make Windows Hello
        // silently fail to find the credential again at sign-in.
        $name = Str::limit((string) $request->query('name', ''), 255, '');

        $options = $generate(new $userModel(['name' => $name]));

        $request->session()->put('passkey.registration_options', WebAuthn::toJson($options));

        return response()->json([
            'options' => WebAuthn::toBrowserArray($options),
        ]);
    }

    /**
     * Create the user, registering its passkey and device UUID.
     */
    public function store(
        PasskeyRegistrationRequest $request,
        StorePasskey $storePasskey,
        RejectSyncedPasskey $rejectSyncedPasskey,
        RedeemPendingInvitation $redeemPendingInvitation
    ): Response {
        $rejectSyncedPasskey($request->credential());

        $userModel = config('auth.providers.users.model');

        $user = $userModel::create(['name' => '']);

        $passkey = $storePasskey(
            $user,
            $request->string('name')->toString(),
            $request->credential(),
            $request->registrationOptions(),
        );

        $device = $user->device()->create(['uuid' => (string) Str::uuid()]);

        Auth::login($user, config('easy-auth.remember_me'));

        $invitation = $redeemPendingInvitation($request, $user);

        // Recorded for every redeemed invitation, not just backup codes:
        // update() needs to know an invitation was in play so it can skip
        // intended() in favor of this definitive destination (see the
        // comment there).
        if ($invitation) {
            $request->session()->put('post_registration_redirect', $invitation->is_backup_code
                ? route('tenants.backup-code.show', $invitation->tenant)
                : route('home'));
        }

        $response = app(PasskeyRegistrationResponse::class)->withPasskey($passkey)->toResponse($request);

        if ($response instanceof JsonResponse) {
            $response->setData([...$response->getData(true), 'device_uuid' => $device->uuid]);
        }

        return $response;
    }

    /**
     * Show the form for completing or editing the user's profile.
     */
    public function edit(Request $request): View
    {
        return view('easy-auth::profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], trans('easy-auth::validation'));

        $request->user()->update($validated);

        $postRegistrationRedirect = $request->session()->pull('post_registration_redirect');

        // A redeemed invitation (tracked via post_registration_redirect, set
        // in store() above) is a more definitive destination than whatever
        // the session's stale url.intended happens to hold from an unrelated
        // earlier visit. Only fall back to intended() for a plain signup
        // with no invitation in play, where returning the guest to whatever
        // protected page they originally tried to reach is the right call.
        $redirect = $postRegistrationRedirect
            ? redirect($postRegistrationRedirect)
            : redirect()->intended(route('home'));

        if ($request->wantsJson()) {
            return response()->json([
                'redirect' => $redirect->getTargetUrl(),
            ]);
        }

        return $redirect;
    }

    /**
     * Show the confirmation page for deleting the account on the
     * authenticated user's own initiative.
     */
    public function show(Request $request): View
    {
        $this->authorize('delete', $request->user());

        return view('easy-auth::profile.delete', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Delete the account on the authenticated user's own initiative.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->authorize('delete', $user);

        $validated = $request->validate([
            'name' => ['required', 'string'],
        ], trans('easy-auth::validation'));

        if ($validated['name'] !== $user->name) {
            return back()->withErrors(['name' => __('easy-auth::account_deletion.name_mismatch')]);
        }

        // Signing out must run before delete(): SessionGuard::logout() cycles and
        // saves the remember token, which would re-insert the row Eloquent
        // had just marked as deleted (save() inserts once exists is false).
        Auth::guard('web')->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('account-deletion.deleted');
    }
}

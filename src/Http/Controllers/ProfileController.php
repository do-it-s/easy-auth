<?php

namespace DoITs\EasyAuth\Http\Controllers;

use DoITs\EasyAuth\Actions\RedeemPendingInvitation;
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
    public function create(): View
    {
        return view('easy-auth::profile.create');
    }

    /**
     * Generate passkey registration options for a not-yet-created user.
     */
    public function passkeyOptions(Request $request, GenerateRegistrationOptions $generate): JsonResponse
    {
        $userModel = config('auth.providers.users.model');

        $options = $generate(new $userModel(['name' => '']));

        $request->session()->put('passkey.registration_options', WebAuthn::toJson($options));

        return response()->json([
            'options' => WebAuthn::toBrowserArray($options),
        ]);
    }

    /**
     * Create the user, registering its passkey and device UUID.
     */
    public function store(PasskeyRegistrationRequest $request, StorePasskey $storePasskey, RedeemPendingInvitation $redeemPendingInvitation): Response
    {
        $userModel = config('auth.providers.users.model');

        $user = $userModel::create(['name' => '']);

        $passkey = $storePasskey(
            $user,
            $request->string('name')->toString(),
            $request->credential(),
            $request->registrationOptions(),
        );

        $device = $user->device()->create(['uuid' => (string) Str::uuid()]);

        Auth::login($user);

        $invitation = $redeemPendingInvitation($request, $user);

        if ($invitation?->is_backup_code) {
            $request->session()->put('post_registration_redirect', route('tenants.backup-code.show', $invitation->tenant));
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
            'currentTenant' => $request->user()->currentTenant(),
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

        $redirectTo = $request->session()->pull('post_registration_redirect', route('home'));

        if ($request->wantsJson()) {
            return response()->json([
                'redirect' => redirect()->intended($redirectTo)->getTargetUrl(),
            ]);
        }

        return redirect()->intended($redirectTo);
    }
}

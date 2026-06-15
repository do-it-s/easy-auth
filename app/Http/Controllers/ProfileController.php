<?php

namespace App\Http\Controllers;

use App\Actions\RedeemPendingInvitation;
use App\Models\User;
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
        return view('profile.create');
    }

    /**
     * Generate passkey registration options for a not-yet-created user.
     */
    public function passkeyOptions(Request $request, GenerateRegistrationOptions $generate): JsonResponse
    {
        $options = $generate(new User(['name' => '']));

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
        $user = User::create(['name' => '']);

        $passkey = $storePasskey(
            $user,
            $request->string('name')->toString(),
            $request->credential(),
            $request->registrationOptions(),
        );

        $device = $user->device()->create(['uuid' => (string) Str::uuid()]);

        Auth::login($user);

        $redeemPendingInvitation($request, $user);

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
        return view('profile.edit', [
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
        ]);

        $request->user()->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'redirect' => redirect()->intended(route('home'))->getTargetUrl(),
            ]);
        }

        return redirect()->intended(route('home'));
    }
}

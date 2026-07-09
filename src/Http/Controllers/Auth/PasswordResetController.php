<?php

namespace DoITs\EasyAuth\Http\Controllers\Auth;

use DoITs\EasyAuth\Events\PasswordResetCompleted;
use DoITs\EasyAuth\Events\PasswordResetting;
use DoITs\EasyAuth\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    /**
     * Show the password reset link request form.
     */
    public function create(): View
    {
        return view('easy-auth::auth.password-request');
    }

    /**
     * Send a password reset link, but only when the submitted email
     * belongs to a fallback (password) user. Otherwise (unknown email,
     * or a passkey-only user) this does nothing — either way the same
     * generic response is returned, to avoid creating an email-existence
     * or auth-method oracle (mirrors SignInController::store()'s identical
     * wording for wrong-password vs wrong-device).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ], trans('easy-auth::validation'));

        $userModel = config('auth.providers.users.model');
        $user = $userModel::where('email', $validated['email'])->first();

        if ($user && $user->password !== null) {
            Password::sendResetLink(['email' => $validated['email']]);
        }

        return back()->with('status', __('easy-auth::password_reset.link_sent'));
    }

    /**
     * Show the new-password form for a given reset token.
     */
    public function edit(Request $request, string $token): View
    {
        return view('easy-auth::auth.password-reset', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Set a new password. Deliberately does not sign the user in: doing so
     * would authenticate the person without ever checking the device,
     * breaking this package's "(person, device) pair" invariant. The user
     * must complete a normal SignInController::store() attempt afterward,
     * which re-runs that check uniformly.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], trans('easy-auth::validation'));

        $status = Password::reset($validated, function ($user, $password) {
            PasswordResetting::dispatch($user, $password);

            $user->forceFill(['password' => $password])->save();

            PasswordResetCompleted::dispatch($user);
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors([
                'email' => __('easy-auth::password_reset.reset_failed'),
            ]);
        }

        return redirect()->route('login')->with('status', __('easy-auth::password_reset.password_updated'));
    }
}

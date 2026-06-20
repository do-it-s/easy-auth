<?php

namespace DoITs\EasyAuth\Http\Controllers\Auth;

use DoITs\EasyAuth\Http\Controllers\Controller;
use DoITs\EasyAuth\Notifications\AccountDeletionLinkNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the email and password login form.
     */
    public function create(): View
    {
        return view('easy-auth::auth.login');
    }

    /**
     * Handle an authentication attempt using email and password.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], trans('easy-auth::validation'));

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => __('easy-auth::auth.login_failed'),
            ]);
        }

        $user = Auth::user();

        if ($user->device && $user->device->uuid !== $request->header('X-Device-Uuid')) {
            Auth::logout();

            // The password matched, but this device never will: no amount of
            // retrying gets this user back in. Email a short-lived signed
            // account-deletion link rather than revealing that fact in the
            // response itself — doing so synchronously would let anyone
            // probing with a guessed or leaked password confirm its
            // correctness even without the right device, the exact oracle
            // this package's generic login_failed wording exists to avoid.
            $user->notify(new AccountDeletionLinkNotification(
                URL::temporarySignedRoute(
                    'account-deletion.show',
                    now()->addMinutes(15),
                    ['id' => $user->id],
                ),
            ));

            throw ValidationException::withMessages([
                'email' => __('easy-auth::auth.login_failed'),
            ]);
        }

        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json([
                'redirect' => redirect()->intended(route('home'))->getTargetUrl(),
            ]);
        }

        return redirect()->intended(route('home'));
    }
}

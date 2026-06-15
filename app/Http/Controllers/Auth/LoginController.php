<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the email and password login form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt using email and password.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'メールアドレス、パスワード、またはこの端末の情報が前回と一致しないため、ログインできません。',
            ]);
        }

        $user = Auth::user();

        if ($user->device && $user->device->uuid !== $request->header('X-Device-Uuid')) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'メールアドレス、パスワード、またはこの端末の情報が前回と一致しないため、ログインできません。',
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

<?php

namespace DoITs\EasyAuth\Http\Controllers\Auth;

use DoITs\EasyAuth\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PwaResyncController extends Controller
{
    /**
     * Show the PWA re-sync page (config('easy-auth.pwa_resync_path')). An
     * already-authenticated visit (e.g. a second tab in the same browsing
     * context) just bounces home; otherwise marks this session as having
     * proved it reached this unguessable URL, which authorizeLoginUsing()
     * (EasyAuthServiceProvider) requires before trusting a passkey ceremony
     * that arrives with no X-Device-Uuid header at all. See index.js's
     * attemptPwaResync(), called by this page's own script.
     */
    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        $request->session()->put('pwa_resync_allowed', true);

        return view('easy-auth::auth.pwa-resync');
    }
}

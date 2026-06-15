<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RedeemPendingInvitation;
use App\Http\Controllers\Controller;
use App\Models\User;
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
        ]);

        $user = User::create([
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

        return response()->json([
            'redirect' => redirect()->intended($redirectTo)->getTargetUrl(),
            'device_uuid' => $device->uuid,
        ]);
    }
}

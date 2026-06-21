<?php

namespace DoITs\EasyAuth\Http\Controllers\Auth;

use DoITs\EasyAuth\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountDeletionController extends Controller
{
    /**
     * Show the confirmation page for deleting an account that is locked
     * out by an unrecognized device, reached only via the short-lived
     * signed link LoginController::store() issues on a device mismatch.
     */
    public function show(Request $request, int $id): View|RedirectResponse
    {
        $userModel = config('auth.providers.users.model');
        $user = $userModel::find($id);

        if (! $user) {
            return redirect()->route('login');
        }

        return view('easy-auth::auth.account-deletion', [
            'user' => $user,
            'signature' => $request->query('signature'),
            'expires' => $request->query('expires'),
        ]);
    }

    /**
     * Delete the account once the user re-types their own name to confirm
     * (the same type-to-confirm pattern TenantLeaveController::destroy()
     * uses, just confirming against the user's own name instead of a
     * tenant's). Unlike that flow, this does not block a sole tenant
     * admin from going through: the user is already locked out with no
     * alternative, so blocking deletion here would just be a permanent
     * dead end.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $userModel = config('auth.providers.users.model');
        $user = $userModel::find($id);

        if (! $user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string'],
        ], trans('easy-auth::validation'));

        if ($validated['name'] !== $user->name) {
            return back()->withErrors(['name' => __('easy-auth::account_deletion.name_mismatch')]);
        }

        $user->delete();

        return redirect()->route('account-deletion.deleted');
    }
}

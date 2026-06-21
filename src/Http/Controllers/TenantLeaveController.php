<?php

namespace DoITs\EasyAuth\Http\Controllers;

use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantLeaveController extends Controller
{
    /**
     * Show the confirmation page for leaving the tenant on the
     * authenticated user's own initiative.
     */
    public function show(Tenant $tenant): View
    {
        $this->authorize('leave', $tenant);

        return view('easy-auth::tenants.leave', [
            'tenant' => $tenant,
        ]);
    }

    /**
     * Leave the tenant on the authenticated user's own initiative.
     */
    public function destroy(Request $request, Tenant $tenant): RedirectResponse
    {
        $user = $request->user();

        $this->authorize('leave', $tenant);

        $validated = $request->validate([
            'name' => ['required', 'string'],
        ], trans('easy-auth::validation'));

        if ($validated['name'] !== $tenant->name) {
            return back()->withErrors(['name' => __('easy-auth::members.name_mismatch')]);
        }

        if ($tenant->isAdministeredBy($user)) {
            $adminCount = $tenant->users()->wherePivot('role', Tenant::ADMIN_ROLE)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['name' => __('easy-auth::members.last_admin_cannot_leave')]);
            }
        }

        $tenant->users()->detach($user);

        return redirect()->route('home');
    }
}

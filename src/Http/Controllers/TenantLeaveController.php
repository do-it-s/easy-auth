<?php

namespace DoITs\EasyAuth\Http\Controllers;

use DoITs\EasyAuth\Events\TenantMemberRemoved;
use DoITs\EasyAuth\Events\TenantMemberRemoving;
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
            'tenant_name' => ['required', 'string'],
        ], trans('easy-auth::validation'));

        if ($validated['tenant_name'] !== $tenant->name) {
            return back()->withErrors(['tenant_name' => __('easy-auth::members.name_mismatch')]);
        }

        TenantMemberRemoving::dispatch($tenant, $user);

        $tenant->users()->detach($user);

        TenantMemberRemoved::dispatch($tenant, $user);

        return redirect()->route('home')
            ->with('status', __('easy-auth::tenants.left', ['tenant' => $tenant->name]));
    }
}

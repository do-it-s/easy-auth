<?php

namespace DoITs\EasyAuth\Http\Controllers;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Events\TenantMemberRemoved;
use DoITs\EasyAuth\Events\TenantMemberRemoving;
use DoITs\EasyAuth\Events\TenantMemberRoleUpdated;
use DoITs\EasyAuth\Events\TenantMemberRoleUpdating;
use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantMemberController extends Controller
{
    /**
     * Display the tenant's member list.
     */
    public function index(Tenant $tenant): View
    {
        $this->authorize('viewAnyMember', $tenant);

        [$admins, $others] = $tenant->users()
            ->orderByPivot('created_at')
            ->get()
            ->partition(fn ($member) => $member->pivot->role === Tenant::ADMIN_ROLE);

        return view('easy-auth::tenants.members.index', [
            'tenant' => $tenant,
            'admins' => $admins,
            'others' => $others,
            'adminCount' => $admins->count(),
        ]);
    }

    /**
     * Update a member's role within the tenant.
     */
    public function update(Request $request, Tenant $tenant, EasyAuthUser $user): RedirectResponse
    {
        $this->authorize('updateMember', [$tenant, $user]);

        abort_unless($tenant->hasMember($user), 404);

        $validated = $request->validate([
            'role' => ['required', Rule::in([Tenant::ADMIN_ROLE, Tenant::MEMBER_ROLE])],
        ], trans('easy-auth::validation'));

        if ($validated['role'] === Tenant::MEMBER_ROLE && $tenant->isAdministeredBy($user)) {
            $adminCount = $tenant->users()->wherePivot('role', Tenant::ADMIN_ROLE)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['role' => __('easy-auth::members.last_admin_cannot_be_demoted')]);
            }
        }

        TenantMemberRoleUpdating::dispatch($tenant, $user, $validated['role']);

        $tenant->users()->updateExistingPivot($user, ['role' => $validated['role']]);

        TenantMemberRoleUpdated::dispatch($tenant, $user, $validated['role']);

        return redirect()->route('tenants.members.index', $tenant);
    }

    /**
     * Remove a member from the tenant.
     */
    public function destroy(Tenant $tenant, EasyAuthUser $user): RedirectResponse
    {
        $this->authorize('removeMember', [$tenant, $user]);

        abort_unless($tenant->hasMember($user), 404);

        if ($tenant->isAdministeredBy($user)) {
            $adminCount = $tenant->users()->wherePivot('role', Tenant::ADMIN_ROLE)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['role' => __('easy-auth::members.last_admin_cannot_be_removed')]);
            }
        }

        TenantMemberRemoving::dispatch($tenant, $user);

        $tenant->users()->detach($user);

        TenantMemberRemoved::dispatch($tenant, $user);

        return redirect()->route('tenants.members.index', $tenant);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
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

        return view('tenants.members.index', [
            'tenant' => $tenant,
            'admins' => $admins,
            'others' => $others,
            'adminCount' => $admins->count(),
        ]);
    }

    /**
     * Update a member's role within the tenant.
     */
    public function update(Request $request, Tenant $tenant, User $user): RedirectResponse
    {
        $this->authorize('updateMember', [$tenant, $user]);

        abort_unless($tenant->hasMember($user), 404);

        $validated = $request->validate([
            'role' => ['required', Rule::in([Tenant::ADMIN_ROLE, Tenant::MEMBER_ROLE])],
        ]);

        if ($validated['role'] === Tenant::MEMBER_ROLE && $tenant->isAdministeredBy($user)) {
            $adminCount = $tenant->users()->wherePivot('role', Tenant::ADMIN_ROLE)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['role' => '最後の管理者は降格できません。']);
            }
        }

        $tenant->users()->updateExistingPivot($user, ['role' => $validated['role']]);

        return redirect()->route('tenants.members.index', $tenant);
    }

    /**
     * Remove a member from the tenant.
     *
     * @todo WBS 4.1: 自主脱退
     */
    public function destroy(Tenant $tenant, User $user): RedirectResponse
    {
        $this->authorize('removeMember', [$tenant, $user]);

        abort_unless($tenant->hasMember($user), 404);

        if ($tenant->isAdministeredBy($user)) {
            $adminCount = $tenant->users()->wherePivot('role', Tenant::ADMIN_ROLE)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['role' => '最後の管理者は脱退させられません。']);
            }
        }

        $tenant->users()->detach($user);

        return redirect()->route('tenants.members.index', $tenant);
    }
}

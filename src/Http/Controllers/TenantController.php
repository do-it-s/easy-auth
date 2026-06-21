<?php

namespace DoITs\EasyAuth\Http\Controllers;

use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('easy-auth::tenants.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
        ], trans('easy-auth::validation'));

        $tenant = Tenant::create(['name' => $validated['tenant_name']]);

        $tenant->users()->attach($request->user(), [
            'role' => Tenant::ADMIN_ROLE,
            'last_accessed_at' => now(),
        ]);

        return redirect()->route('tenants.backup-code.show', $tenant);
    }

    /**
     * Show the confirmation page for deleting the tenant.
     */
    public function show(Tenant $tenant): View
    {
        $this->authorize('delete', $tenant);

        return view('easy-auth::tenants.delete', [
            'tenant' => $tenant,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tenant $tenant): View
    {
        $this->authorize('update', $tenant);

        return view('easy-auth::tenants.edit', [
            'tenant' => $tenant,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('update', $tenant);

        $validated = $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'member_invites_enabled' => ['nullable', 'boolean'],
        ], trans('easy-auth::validation'));

        $tenant->update([
            'name' => $validated['tenant_name'],
            'member_invites_enabled' => $request->boolean('member_invites_enabled'),
        ]);

        return redirect()->route('tenants.edit', $tenant);
    }

    /**
     * Delete the tenant, along with its memberships and invitations
     * (both cascade on tenant_id at the database level).
     */
    public function destroy(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('delete', $tenant);

        $validated = $request->validate([
            'tenant_name' => ['required', 'string'],
        ], trans('easy-auth::validation'));

        if ($validated['tenant_name'] !== $tenant->name) {
            return back()->withErrors(['tenant_name' => __('easy-auth::members.name_mismatch')]);
        }

        $tenant->delete();

        return redirect()->route('home');
    }
}

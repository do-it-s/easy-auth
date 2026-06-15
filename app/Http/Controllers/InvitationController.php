<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Tenant;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvitationController extends Controller
{
    /**
     * Display the tenant's invitations.
     */
    public function index(Tenant $tenant): View
    {
        $this->authorize('viewAny', [Invitation::class, $tenant]);

        return view('tenants.invitations.index', [
            'tenant' => $tenant,
            'invitations' => $tenant->invitations()->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new invitation.
     */
    public function create(Tenant $tenant): View
    {
        $this->authorize('create', [Invitation::class, $tenant]);

        $invitationUrl = session('invitation_url');

        return view('tenants.invitations.create', [
            'tenant' => $tenant,
            'isAdmin' => $tenant->isAdministeredBy(request()->user()),
            'invitationUrl' => $invitationUrl,
            'invitationQrCode' => $invitationUrl
                ? (new Builder)->build(data: $invitationUrl)->getDataUri()
                : null,
        ]);
    }

    /**
     * Store a newly created invitation in storage.
     */
    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('create', [Invitation::class, $tenant]);

        $allowedRoles = $tenant->isAdministeredBy($request->user())
            ? [Tenant::ADMIN_ROLE, Tenant::MEMBER_ROLE]
            : [Tenant::MEMBER_ROLE];

        $validated = $request->validate([
            'role' => ['required', Rule::in($allowedRoles)],
            'label' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $token = Invitation::generateToken();

        $tenant->invitations()->create([
            'role' => $validated['role'],
            'token' => Invitation::hashToken($token),
            'label' => $validated['label'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('tenants.invitations.create', $tenant)
            ->with('invitation_url', route('invitations.show', $token));
    }

    /**
     * Revoke the specified invitation.
     */
    public function destroy(Tenant $tenant, Invitation $invitation): RedirectResponse
    {
        $this->authorize('delete', $invitation);

        $invitation->update(['used_at' => now()]);

        return redirect()->route('tenants.invitations.index', $tenant);
    }
}

<?php

namespace DoITs\EasyAuth\Http\Controllers;

use DoITs\EasyAuth\Events\InvitationCreated;
use DoITs\EasyAuth\Events\InvitationCreating;
use DoITs\EasyAuth\Events\InvitationRevoked;
use DoITs\EasyAuth\Events\InvitationRevoking;
use DoITs\EasyAuth\Models\Invitation;
use DoITs\EasyAuth\Models\Tenant;
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

        return view('easy-auth::tenants.invitations.index', [
            'tenant' => $tenant,
            'invitations' => $tenant->invitations()
                ->where('is_backup_code', false)
                ->with('redemptions.user')
                ->latest()
                ->paginate(config('easy-auth.invitations_per_page') ?? PHP_INT_MAX),
        ]);
    }

    /**
     * Show the form for creating a new invitation.
     */
    public function create(Tenant $tenant): View
    {
        $this->authorize('create', [Invitation::class, $tenant]);

        $invitationUrl = session('invitation_url');

        return view('easy-auth::tenants.invitations.create', [
            'tenant' => $tenant,
            'isAdmin' => $tenant->isAdministeredBy(request()->user()),
            'invitationUrl' => $invitationUrl,
            'invitationQrCode' => $invitationUrl
                ? (new Builder)->build(data: $invitationUrl)->getDataUri()
                : null,
            'defaultExpiresAt' => now()->addMinutes(Invitation::DEFAULT_EXPIRATION_MINUTES)->format('Y-m-d\TH:i'),
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
            'max_uses' => ['nullable', 'integer', 'min:1'],
        ], trans('easy-auth::validation'));

        // The multi-use knob is system-administration territory (see
        // config('easy-auth.multi_use_invitations')'s docblock), so a
        // submitted max_uses is only honored while it's enabled; otherwise
        // every invitation is forced single-use regardless of request input.
        $maxUses = config('easy-auth.multi_use_invitations') ? ($validated['max_uses'] ?? null) : 1;

        // Same pattern for expires_at (see config('easy-auth.custom_invitation_expiration')'s
        // docblock): a submitted value is only honored while it's enabled;
        // otherwise every invitation is forced to the fixed default expiry
        // regardless of request input.
        $expiresAt = config('easy-auth.custom_invitation_expiration')
            ? ($validated['expires_at'] ?? null)
            : now()->addMinutes(Invitation::DEFAULT_EXPIRATION_MINUTES);

        $token = Invitation::generateToken();

        InvitationCreating::dispatch($tenant, $request->user(), $validated);

        $invitation = $tenant->invitations()->create([
            'role' => $validated['role'],
            'token' => Invitation::hashToken($token),
            'label' => $validated['label'] ?? null,
            'expires_at' => $expiresAt,
            'max_uses' => $maxUses,
            'created_by' => $request->user()->id,
        ]);

        InvitationCreated::dispatch($invitation);

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

        InvitationRevoking::dispatch($invitation);

        $invitation->update(['revoked_at' => now()]);

        InvitationRevoked::dispatch($invitation);

        return redirect()->route('tenants.invitations.index', $tenant);
    }
}

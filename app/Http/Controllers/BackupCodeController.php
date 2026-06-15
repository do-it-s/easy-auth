<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Tenant;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BackupCodeController extends Controller
{
    /**
     * Show the tenant's administrator backup code status.
     */
    public function show(Tenant $tenant): View
    {
        $this->authorize('update', $tenant);

        $invitationUrl = session('invitation_url');

        return view('tenants.backup-code.show', [
            'tenant' => $tenant,
            'hasUsableBackupCode' => $tenant->hasUsableBackupCode(),
            'invitationUrl' => $invitationUrl,
            'invitationQrCode' => $invitationUrl
                ? (new Builder)->build(data: $invitationUrl)->getDataUri()
                : null,
        ]);
    }

    /**
     * Issue a new administrator backup code, invalidating any existing one.
     */
    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('update', $tenant);

        $tenant->invitations()
            ->where('is_backup_code', true)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $token = Invitation::generateToken();

        $tenant->invitations()->create([
            'role' => Tenant::ADMIN_ROLE,
            'token' => Invitation::hashToken($token),
            'label' => '緊急用バックアップコード',
            'expires_at' => null,
            'is_backup_code' => true,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('tenants.backup-code.show', $tenant)
            ->with('invitation_url', route('invitations.show', $token));
    }
}

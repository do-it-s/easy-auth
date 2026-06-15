<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantSwitchController extends Controller
{
    /**
     * Switch the authenticated user's current tenant.
     */
    public function switch(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('switch', $tenant);

        $tenant->users()->updateExistingPivot($request->user()->id, [
            'last_accessed_at' => now(),
        ]);

        return redirect()->route('home');
    }
}

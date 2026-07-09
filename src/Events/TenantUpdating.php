<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

/**
 * Dispatched by TenantController::update() immediately before $tenant->update(),
 * since that call only fills the hardcoded 'name'/'member_invites_enabled'
 * fields and an app adding its own tenant fields needs a point to persist
 * them alongside it. Carries the raw $request (rather than a validated array,
 * unlike ProfileUpdating) so listeners can call $request->boolean(...) etc.
 * for their own fields, which were never part of this controller's own
 * validate() call and so have no validated equivalent to hand over.
 */
class TenantUpdating
{
    use Dispatchable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly Request $request,
    ) {}
}

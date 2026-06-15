<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    /**
     * Determine whether the user can update the tenant's settings.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $tenant->isAdministeredBy($user);
    }
}

<?php

namespace DoITs\EasyAuth\Contracts;

use DoITs\EasyAuth\Models\Device;
use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Passkeys\Contracts\PasskeyUser;

/**
 * Implemented by the host application's own User model (alongside
 * Concerns\IsEasyAuthUser) so the package never needs to own the User
 * model directly. Extends laravel/passkeys' own PasskeyUser contract so
 * the host only has to declare one interface instead of two.
 */
interface EasyAuthUser extends PasskeyUser
{
    /**
     * The device this user is bound to.
     *
     * @return HasOne<Device, $this>
     */
    public function device(): HasOne;

    /**
     * The tenants this user belongs to.
     *
     * @return BelongsToMany<Tenant, $this>
     */
    public function tenants(): BelongsToMany;

    /**
     * The tenant the user most recently accessed, if any.
     */
    public function currentTenant(): ?Tenant;

    /**
     * Whether this user is this package's system administrator, per
     * config('easy-auth.sysadmin_user_id').
     */
    public function isSysAdmin(): bool;
}

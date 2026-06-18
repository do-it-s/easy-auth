<?php

namespace DoITs\EasyAuth\Contracts;

use DoITs\EasyAuth\Models\Device;
use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Implemented by the host application's own User model (alongside
 * Concerns\IsEasyAuthUser) so the package never needs to own the User
 * model directly, mirroring how laravel/passkeys' own PasskeyUser
 * contract is implemented by the host's User model.
 */
interface EasyAuthUser
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
}

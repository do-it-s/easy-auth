<?php

namespace DoITs\EasyAuth\Concerns;

use DoITs\EasyAuth\Models\Device;
use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Satisfies the Contracts\EasyAuthUser contract. The host application's
 * User model should `use` this trait and `implements EasyAuthUser`.
 */
trait IsEasyAuthUser
{
    /**
     * The device this user is bound to.
     *
     * @return HasOne<Device, $this>
     */
    public function device(): HasOne
    {
        return $this->hasOne(Device::class);
    }

    /**
     * The tenants this user belongs to.
     *
     * @return BelongsToMany<Tenant, $this>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)
            ->withPivot(['role', 'last_accessed_at'])
            ->withTimestamps();
    }

    /**
     * The tenant the user most recently accessed, if any.
     */
    public function currentTenant(): ?Tenant
    {
        return $this->tenants()
            ->orderByPivot('last_accessed_at', 'desc')
            ->first();
    }
}

<?php

namespace DoITs\EasyAuth\Concerns;

use DoITs\EasyAuth\Models\Device;
use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Passkeys\PasskeyAuthenticatable;

/**
 * Satisfies the Contracts\EasyAuthUser contract. The host application's
 * User model should `use` this trait and `implements EasyAuthUser`.
 * Includes laravel/passkeys' own PasskeyAuthenticatable trait so the
 * host only has to declare one trait instead of two.
 */
trait IsEasyAuthUser
{
    use PasskeyAuthenticatable;

    /**
     * Get the username for WebAuthn registration. See getPasskeyDisplayName().
     */
    public function getPasskeyUsername(): string
    {
        return $this->passkeyIdentity();
    }

    /**
     * Get the display name for WebAuthn registration.
     *
     * Overrides PasskeyAuthenticatable's version, which falls back via `??`
     * (unhandled for an explicitly empty string, as used for a not-yet-created
     * user mid passkey registration) and prefers email over name. An empty
     * name/displayName reaching the authenticator has been observed to make
     * Windows Hello silently fail to find the credential again at sign-in.
     *
     * email is deliberately not consulted here: easy-auth's password
     * registration path exists only as a fallback for devices that couldn't
     * use a passkey in the first place, so an account having an email is
     * incidental to whether it actually uses passkeys, not a signal to
     * prefer over its name.
     */
    public function getPasskeyDisplayName(): string
    {
        return $this->passkeyIdentity();
    }

    /**
     * First of name/auth identifier that isn't null or an empty string, cast
     * to string. Falls back to a fixed placeholder when both are blank.
     */
    protected function passkeyIdentity(): string
    {
        foreach ([$this->getAttribute('name'), $this->getAuthIdentifier()] as $candidate) {
            if (filled($candidate)) {
                return (string) $candidate;
            }
        }

        return trans('easy-auth::profile.passkey_pending_identity');
    }

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

    /**
     * Whether this user is this package's system administrator, per
     * config('easy-auth.sysadmin_user_id').
     */
    public function isSysAdmin(): bool
    {
        $sysadminUserId = config('easy-auth.sysadmin_user_id');

        return $sysadminUserId !== null && (int) $sysadminUserId === (int) $this->getAuthIdentifier();
    }
}

<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'member_invites_enabled'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /**
     * Namespace used to reserve role identifiers for this package.
     */
    public const ROLE_NAMESPACE = 'shocking-auth';

    /**
     * The role that grants tenant management permissions (member removal,
     * invitation management, etc.). Reserved across the whole package.
     */
    public const ADMIN_ROLE = self::ROLE_NAMESPACE.'.tenant-admin';

    /**
     * The default role granted to ordinary members.
     */
    public const MEMBER_ROLE = 'member';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'member_invites_enabled' => 'boolean',
        ];
    }

    /**
     * The users that belong to the tenant.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'last_accessed_at'])
            ->withTimestamps();
    }

    /**
     * The invitations issued for this tenant.
     *
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Determine whether the given user has tenant management permissions.
     */
    public function isAdministeredBy(User $user): bool
    {
        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', self::ADMIN_ROLE)
            ->exists();
    }

    /**
     * Determine whether the given user belongs to this tenant, regardless of role.
     */
    public function hasMember(User $user): bool
    {
        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->exists();
    }
}

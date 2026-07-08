<?php

namespace DoITs\EasyAuth\Models;

use Database\Factories\InvitationFactory;
use DoITs\EasyAuth\Contracts\EasyAuthUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['tenant_id', 'role', 'token', 'label', 'expires_at', 'max_uses', 'created_by', 'used_at', 'redeemed_by', 'revoked_at', 'is_backup_code'])]
class Invitation extends Model
{
    use HasFactory;

    /**
     * The default validity period for newly issued invitations, in minutes.
     */
    public const DEFAULT_EXPIRATION_MINUTES = 30;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
            'is_backup_code' => 'boolean',
        ];
    }

    /**
     * The tenant this invitation grants access to.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The user who created this invitation.
     *
     * @return BelongsTo<EasyAuthUser, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    /**
     * The user who redeemed this invitation.
     *
     * Only meaningful for backup codes, which still record a single
     * redeemer here. Ordinary (possibly multi-use) invitations record
     * every redemption in {@see redemptions()} instead.
     *
     * @return BelongsTo<EasyAuthUser, $this>
     */
    public function redeemer(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'redeemed_by');
    }

    /**
     * The redemption log for this invitation. Not used by backup codes,
     * which are single-shot and reissued rather than reused.
     *
     * @return HasMany<InvitationRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(InvitationRedemption::class);
    }

    /**
     * Generate a new random invitation token.
     */
    public static function generateToken(): string
    {
        return Str::random(48);
    }

    /**
     * Hash an invitation token for storage and lookup.
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Determine whether an administrator has explicitly disabled this
     * invitation, independent of expiration or exhausted uses.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Determine whether this invitation has no redemptions left. Backup
     * codes remain single-shot (`used_at`); ordinary invitations are
     * exhausted once their redemption count reaches `max_uses` (a null
     * `max_uses` means unlimited, mirroring `expires_at`'s null-means-
     * unbounded convention).
     */
    public function isUsed(): bool
    {
        if ($this->is_backup_code) {
            return $this->used_at !== null;
        }

        return $this->max_uses !== null && $this->redemptions()->count() >= $this->max_uses;
    }

    public function isUsable(): bool
    {
        return ! $this->isExpired() && ! $this->isRevoked() && ! $this->isUsed();
    }

    /**
     * The number of redemptions left before this invitation is exhausted,
     * or null if it has unlimited uses (backup codes included, since they
     * are reissued rather than counted).
     */
    public function usesRemaining(): ?int
    {
        if ($this->is_backup_code || $this->max_uses === null) {
            return null;
        }

        return max(0, $this->max_uses - $this->redemptions()->count());
    }

    /**
     * Determine whether the given user can redeem this invitation.
     *
     * A user who is not yet a member of the tenant can always redeem an
     * invitation to join it. An existing member can redeem any invitation
     * that does not downgrade their role (i.e. an administrator cannot
     * redeem a member-role invitation).
     */
    public function canBeRedeemedBy(EasyAuthUser $user): bool
    {
        if (! $this->tenant->hasMember($user)) {
            return true;
        }

        return ! ($this->tenant->isAdministeredBy($user) && $this->role !== Tenant::ADMIN_ROLE);
    }

    /**
     * Add the given user to this invitation's tenant with the invited role
     * (or promote them to it, if already a member), and record the
     * redemption.
     */
    public function redeemFor(EasyAuthUser $user): void
    {
        if ($this->tenant->hasMember($user)) {
            $this->tenant->users()->updateExistingPivot($user, [
                'role' => $this->role,
                'last_accessed_at' => now(),
            ]);
        } else {
            $this->tenant->users()->attach($user, [
                'role' => $this->role,
                'last_accessed_at' => now(),
            ]);
        }

        if ($this->is_backup_code) {
            $this->update([
                'used_at' => now(),
                'redeemed_by' => $user->id,
            ]);

            return;
        }

        $this->redemptions()->create([
            'user_id' => $user->id,
            'redeemed_at' => now(),
        ]);
    }

    /**
     * @return InvitationFactory
     */
    protected static function newFactory()
    {
        return InvitationFactory::new();
    }
}

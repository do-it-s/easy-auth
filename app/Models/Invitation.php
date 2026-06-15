<?php

namespace App\Models;

use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['tenant_id', 'role', 'token', 'label', 'expires_at', 'created_by', 'used_at', 'redeemed_by'])]
class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The user who redeemed this invitation.
     *
     * @return BelongsTo<User, $this>
     */
    public function redeemer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
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

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed();
    }

    /**
     * Add the given user to this invitation's tenant with the invited role,
     * and mark the invitation as used.
     */
    public function redeemFor(User $user): void
    {
        $this->tenant->users()->attach($user, [
            'role' => $this->role,
            'last_accessed_at' => now(),
        ]);

        $this->update([
            'used_at' => now(),
            'redeemed_by' => $user->id,
        ]);
    }
}

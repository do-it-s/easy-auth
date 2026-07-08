<?php

namespace Database\Factories;

use DoITs\EasyAuth\Models\Invitation;
use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userModel = config('auth.providers.users.model');

        return [
            'tenant_id' => Tenant::factory(),
            'role' => Tenant::MEMBER_ROLE,
            'token' => Invitation::hashToken(Invitation::generateToken()),
            'label' => null,
            'expires_at' => now()->addWeek(),
            'max_uses' => 1,
            'created_by' => $userModel::factory(),
        ];
    }

    /**
     * Indicate how many times the invitation can be redeemed (null for
     * unlimited uses).
     */
    public function reusable(?int $maxUses = null): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses' => $maxUses,
        ]);
    }

    /**
     * Indicate that the invitation grants the tenant administrator role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Tenant::ADMIN_ROLE,
        ]);
    }

    /**
     * Indicate that the invitation never expires.
     */
    public function eternal(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }

    /**
     * Indicate that the invitation is an administrator backup code.
     */
    public function backupCode(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Tenant::ADMIN_ROLE,
            'label' => __('easy-auth::backup_code.emergency_label'),
            'expires_at' => null,
            'is_backup_code' => true,
        ]);
    }

    /**
     * Indicate that the invitation has already been used (its redemptions
     * have reached max_uses, or, for backup codes, used_at is set).
     */
    public function used(): static
    {
        $userModel = config('auth.providers.users.model');

        return $this->state(fn (array $attributes) => [
            'used_at' => now(),
            'redeemed_by' => $userModel::factory(),
        ])->afterCreating(function (Invitation $invitation) use ($userModel) {
            if (! $invitation->is_backup_code) {
                $invitation->redemptions()->create([
                    'user_id' => $userModel::factory()->create()->id,
                    'redeemed_at' => now(),
                ]);
            }
        });
    }

    /**
     * Indicate that the invitation has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}

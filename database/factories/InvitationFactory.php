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
            'created_by' => $userModel::factory(),
        ];
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
            'label' => '緊急用バックアップコード',
            'expires_at' => null,
            'is_backup_code' => true,
        ]);
    }

    /**
     * Indicate that the invitation has already been used.
     */
    public function used(): static
    {
        $userModel = config('auth.providers.users.model');

        return $this->state(fn (array $attributes) => [
            'used_at' => now(),
            'redeemed_by' => $userModel::factory(),
        ]);
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

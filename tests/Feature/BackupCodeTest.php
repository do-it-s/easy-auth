<?php

use DoITs\EasyAuth\Models\Invitation;
use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Tests\Fixtures\User;

function attachAdmin(Tenant $tenant, User $user): void
{
    $tenant->users()->attach($user, [
        'role' => Tenant::ADMIN_ROLE,
        'last_accessed_at' => now(),
    ]);
}

test('admin can view the backup code page', function () {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachAdmin($tenant, $admin);

    $this->actingAs($admin)->get(route('tenants.backup-code.show', $tenant))->assertOk();
});

test('non admin cannot view the backup code page', function () {
    $member = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($member)->get(route('tenants.backup-code.show', $tenant))->assertForbidden();
});

test('admin can issue a backup code', function () {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachAdmin($tenant, $admin);

    $response = $this->actingAs($admin)->post(route('tenants.backup-code.store', $tenant));

    $response->assertRedirect(route('tenants.backup-code.show', $tenant));
    $response->assertSessionHas('invitation_url');

    $backupCode = Invitation::where('tenant_id', $tenant->id)->where('is_backup_code', true)->first();
    expect($backupCode)->not->toBeNull();
    expect($backupCode->role)->toBe(Tenant::ADMIN_ROLE);
    expect($backupCode->expires_at)->toBeNull();
    expect($backupCode->isUsable())->toBeTrue();

    $rawToken = basename((string) session('invitation_url'));
    expect(Invitation::hashToken($rawToken))->toBe($backupCode->token);
});

test('reissuing a backup code invalidates the previous one', function () {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachAdmin($tenant, $admin);

    $first = Invitation::factory()->for($tenant)->backupCode()->create();

    $this->actingAs($admin)->post(route('tenants.backup-code.store', $tenant));

    expect($first->refresh()->isUsed())->toBeTrue();

    $current = Invitation::where('tenant_id', $tenant->id)->where('is_backup_code', true)->where('id', '!=', $first->id)->first();
    expect($current->isUsable())->toBeTrue();
});

test('creating a tenant redirects to the backup code page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('tenants.store'), [
        'name' => '新しいテナント',
    ]);

    $tenant = Tenant::where('name', '新しいテナント')->first();

    $response->assertRedirect(route('tenants.backup-code.show', $tenant));
});

test('authenticated user redeeming a backup code becomes admin and is sent to the backup code page', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->backupCode()->create([
        'token' => Invitation::hashToken($token),
    ]);

    $response = $this->actingAs($user)->post(route('invitations.redeem', $token));

    $response->assertRedirect(route('tenants.backup-code.show', $tenant));
    expect($tenant->users()->wherePivot('user_id', $user->id)->first()->pivot->role)->toBe(Tenant::ADMIN_ROLE);
    expect($invitation->refresh()->isUsed())->toBeTrue();
});

test('an existing member can redeem a backup code to be promoted to admin', function () {
    $member = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->backupCode()->create([
        'token' => Invitation::hashToken($token),
    ]);

    $this->actingAs($member)->get(route('invitations.show', $token))->assertOk();

    $response = $this->actingAs($member)->post(route('invitations.redeem', $token));

    $response->assertRedirect(route('tenants.backup-code.show', $tenant));
    expect($tenant->users()->wherePivot('user_id', $member->id)->first()->pivot->role)->toBe(Tenant::ADMIN_ROLE);
    expect($invitation->refresh()->isUsed())->toBeTrue();
});

test('an existing admin redeeming their own backup code consumes it and is sent to reissue', function () {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachAdmin($tenant, $admin);

    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->backupCode()->create([
        'token' => Invitation::hashToken($token),
    ]);

    $this->actingAs($admin)->get(route('invitations.show', $token))->assertOk();

    $response = $this->actingAs($admin)->post(route('invitations.redeem', $token));

    $response->assertRedirect(route('tenants.backup-code.show', $tenant));
    expect($invitation->refresh()->isUsed())->toBeTrue();
});

test('backup codes do not appear in the invitation list', function () {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachAdmin($tenant, $admin);

    Invitation::factory()->for($tenant)->backupCode()->create();
    $regular = Invitation::factory()->for($tenant)->create();

    $response = $this->actingAs($admin)->get(route('tenants.invitations.index', $tenant));

    $response->assertSee($regular->label ?? 'メンバー');
    $response->assertDontSee('緊急用バックアップコード');
});

test('a backup code cannot be revoked through the invitation list', function () {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachAdmin($tenant, $admin);

    $backupCode = Invitation::factory()->for($tenant)->backupCode()->create();

    $response = $this->actingAs($admin)->delete(route('tenants.invitations.destroy', [$tenant, $backupCode]));

    $response->assertForbidden();
    expect($backupCode->refresh()->isUsed())->toBeFalse();
});

test('registering with email and password via a backup code becomes admin and is sent to the backup code page', function () {
    $tenant = Tenant::factory()->create();
    $token = Invitation::generateToken();
    Invitation::factory()->for($tenant)->backupCode()->create([
        'token' => Invitation::hashToken($token),
    ]);

    $response = $this->withSession(['pending_invitation_token' => $token])->postJson('/profile-password', [
        'name' => 'Taro',
        'email' => 'taro@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertOk()->assertJson(['redirect' => route('tenants.backup-code.show', $tenant)]);

    $user = User::where('email', 'taro@example.com')->first();
    expect($tenant->users()->wherePivot('user_id', $user->id)->first()->pivot->role)->toBe(Tenant::ADMIN_ROLE);
});

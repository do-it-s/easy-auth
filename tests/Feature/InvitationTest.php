<?php

use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;

function attachTenantMember(Tenant $tenant, User $user, string $role): void
{
    $tenant->users()->attach($user, [
        'role' => $role,
        'last_accessed_at' => now(),
    ]);
}

test('tenant admin can view and issue invitations', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $this->actingAs($admin)->get(route('tenants.invitations.index', $tenant))->assertOk();
    $this->actingAs($admin)->get(route('tenants.invitations.create', $tenant))->assertOk();

    $response = $this->actingAs($admin)->post(route('tenants.invitations.store', $tenant), [
        'role' => Tenant::MEMBER_ROLE,
        'label' => 'テストメンバー招待',
        'expires_at' => now()->addWeek()->format('Y-m-d\TH:i'),
    ]);

    $response->assertRedirect(route('tenants.invitations.create', $tenant));
    $response->assertSessionHas('invitation_url');

    $invitation = Invitation::where('tenant_id', $tenant->id)->first();
    expect($invitation->role)->toBe(Tenant::MEMBER_ROLE);
    expect($invitation->label)->toBe('テストメンバー招待');
    expect($invitation->expires_at)->not->toBeNull();
    expect($invitation->token)->not->toBeNull();

    $rawToken = basename((string) session('invitation_url'));
    expect(Invitation::hashToken($rawToken))->toBe($invitation->token);
});

test('admin can issue an eternal admin invitation', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $response = $this->actingAs($admin)->post(route('tenants.invitations.store', $tenant), [
        'role' => Tenant::ADMIN_ROLE,
        'expires_at' => '',
    ]);

    $response->assertRedirect(route('tenants.invitations.create', $tenant));

    $invitation = Invitation::where('tenant_id', $tenant->id)->first();
    expect($invitation->role)->toBe(Tenant::ADMIN_ROLE);
    expect($invitation->expires_at)->toBeNull();
});

test('members can issue member invitations when member invites are enabled', function () {
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create(['member_invites_enabled' => true]);
    attachTenantMember($tenant, $member, Tenant::MEMBER_ROLE);

    $this->actingAs($member)->get(route('tenants.invitations.create', $tenant))->assertOk();

    $response = $this->actingAs($member)->post(route('tenants.invitations.store', $tenant), [
        'role' => Tenant::MEMBER_ROLE,
    ]);

    $response->assertRedirect(route('tenants.invitations.create', $tenant));

    $invitation = Invitation::where('tenant_id', $tenant->id)->first();
    expect($invitation->role)->toBe(Tenant::MEMBER_ROLE);
});

test('members cannot issue admin invitations even when member invites are enabled', function () {
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create(['member_invites_enabled' => true]);
    attachTenantMember($tenant, $member, Tenant::MEMBER_ROLE);

    $response = $this->actingAs($member)->post(route('tenants.invitations.store', $tenant), [
        'role' => Tenant::ADMIN_ROLE,
    ]);

    $response->assertSessionHasErrors('role');
    expect(Invitation::where('tenant_id', $tenant->id)->count())->toBe(0);
});

test('members cannot issue invitations when member invites are disabled', function () {
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create(['member_invites_enabled' => false]);
    attachTenantMember($tenant, $member, Tenant::MEMBER_ROLE);

    $this->actingAs($member)->get(route('tenants.invitations.create', $tenant))->assertForbidden();

    $response = $this->actingAs($member)->post(route('tenants.invitations.store', $tenant), [
        'role' => Tenant::MEMBER_ROLE,
    ]);

    $response->assertForbidden();
});

test('non members cannot view or issue invitations', function () {
    $outsider = User::factory()->create(['name' => 'Outsider']);
    $tenant = Tenant::factory()->create();

    $this->actingAs($outsider)->get(route('tenants.invitations.index', $tenant))->assertForbidden();
    $this->actingAs($outsider)->get(route('tenants.invitations.create', $tenant))->assertForbidden();
});

test('only admins can list or revoke invitations regardless of member invites setting', function () {
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create(['member_invites_enabled' => true]);
    attachTenantMember($tenant, $member, Tenant::MEMBER_ROLE);

    $invitation = Invitation::factory()->for($tenant)->create();

    $this->actingAs($member)->get(route('tenants.invitations.index', $tenant))->assertForbidden();

    $response = $this->actingAs($member)->delete(route('tenants.invitations.destroy', [$tenant, $invitation]));
    $response->assertForbidden();

    expect($invitation->refresh()->isUsed())->toBeFalse();
});

test('admin can revoke an invitation', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $invitation = Invitation::factory()->for($tenant)->create();

    $response = $this->actingAs($admin)->delete(route('tenants.invitations.destroy', [$tenant, $invitation]));

    $response->assertRedirect(route('tenants.invitations.index', $tenant));
    expect($invitation->refresh()->isUsed())->toBeTrue();
});

test('guest opening an invitation link is sent to registration with the token stored', function () {
    $token = Invitation::generateToken();
    $tenant = Tenant::factory()->create();
    Invitation::factory()->for($tenant)->create(['token' => Invitation::hashToken($token)]);

    $response = $this->get(route('invitations.show', $token));

    $response->assertRedirect(route('profile.create'));
    $this->assertSame($token, session('pending_invitation_token'));
});

test('authenticated non member can redeem an invitation and joins the tenant', function () {
    $user = User::factory()->create(['name' => 'New Member']);
    $tenant = Tenant::factory()->create();
    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->create([
        'token' => Invitation::hashToken($token),
        'role' => Tenant::MEMBER_ROLE,
    ]);

    $this->actingAs($user)->get(route('invitations.show', $token))->assertOk();

    $response = $this->actingAs($user)->post(route('invitations.redeem', $token));

    $response->assertRedirect(route('home'));
    expect($tenant->hasMember($user))->toBeTrue();
    expect($tenant->users()->wherePivot('user_id', $user->id)->first()->pivot->role)->toBe(Tenant::MEMBER_ROLE);

    $invitation->refresh();
    expect($invitation->isUsed())->toBeTrue();
    expect($invitation->redeemed_by)->toBe($user->id);
});

test('a member already in the tenant sees an already member message without side effects', function () {
    $user = User::factory()->create(['name' => 'Existing Member']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $user, Tenant::MEMBER_ROLE);

    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->create(['token' => Invitation::hashToken($token)]);

    $this->actingAs($user)->get(route('invitations.show', $token))->assertOk();

    $response = $this->actingAs($user)->post(route('invitations.redeem', $token));

    $response->assertOk();
    expect($invitation->refresh()->isUsed())->toBeFalse();
});

test('an expired invitation cannot be redeemed', function () {
    $token = Invitation::generateToken();
    $tenant = Tenant::factory()->create();
    Invitation::factory()->for($tenant)->expired()->create(['token' => Invitation::hashToken($token)]);

    $this->get(route('invitations.show', $token))->assertOk();
});

test('a used invitation cannot be redeemed', function () {
    $token = Invitation::generateToken();
    $tenant = Tenant::factory()->create();
    Invitation::factory()->for($tenant)->used()->create(['token' => Invitation::hashToken($token)]);

    $this->get(route('invitations.show', $token))->assertOk();
});

test('admin can update tenant settings including member invites toggle', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create(['member_invites_enabled' => true]);
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $response = $this->actingAs($admin)->patch(route('tenants.update', $tenant), [
        'name' => 'リネーム済みテナント',
        'member_invites_enabled' => false,
    ]);

    $response->assertRedirect(route('tenants.edit', $tenant));

    $tenant->refresh();
    expect($tenant->name)->toBe('リネーム済みテナント');
    expect($tenant->member_invites_enabled)->toBeFalse();
});

test('non admin cannot update tenant settings', function () {
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $member, Tenant::MEMBER_ROLE);

    $response = $this->actingAs($member)->get(route('tenants.edit', $tenant));

    $response->assertForbidden();
});

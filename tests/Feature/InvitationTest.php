<?php

use DoITs\EasyAuth\Events\InvitationCreated;
use DoITs\EasyAuth\Events\InvitationCreating;
use DoITs\EasyAuth\Events\InvitationRedeemed;
use DoITs\EasyAuth\Events\InvitationRedeeming;
use DoITs\EasyAuth\Events\InvitationRevoked;
use DoITs\EasyAuth\Events\InvitationRevoking;
use DoITs\EasyAuth\Models\Invitation;
use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Support\Facades\Event;

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
        'label' => 'Test member invite',
        'expires_at' => now()->addWeek()->format('Y-m-d\TH:i'),
    ]);

    $response->assertRedirect(route('tenants.invitations.create', $tenant));
    $response->assertSessionHas('invitation_url');

    $invitation = Invitation::where('tenant_id', $tenant->id)->first();
    expect($invitation->role)->toBe(Tenant::MEMBER_ROLE);
    expect($invitation->label)->toBe('Test member invite');
    expect($invitation->expires_at)->not->toBeNull();
    expect($invitation->token)->not->toBeNull();

    $rawToken = basename((string) session('invitation_url'));
    expect(Invitation::hashToken($rawToken))->toBe($invitation->token);
});

test('admin can issue an eternal admin invitation when custom invitation expiration is enabled', function () {
    config(['easy-auth.custom_invitation_expiration' => true]);
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

test('a submitted expires_at is ignored and forced to the default when custom invitation expiration is disabled', function () {
    expect(config('easy-auth.custom_invitation_expiration'))->toBeFalse();
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $response = $this->actingAs($admin)->post(route('tenants.invitations.store', $tenant), [
        'role' => Tenant::MEMBER_ROLE,
        'expires_at' => now()->addWeek()->format('Y-m-d\TH:i'),
    ]);

    $response->assertRedirect(route('tenants.invitations.create', $tenant));

    $invitation = Invitation::where('tenant_id', $tenant->id)->first();
    expect($invitation->expires_at)->not->toBeNull();
    expect($invitation->expires_at->diffInMinutes(now()))->toBeLessThanOrEqual(Invitation::DEFAULT_EXPIRATION_MINUTES);
});

test('the expires_at field is hidden from the invitation form unless custom invitation expiration is enabled', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $this->actingAs($admin)
        ->get(route('tenants.invitations.create', $tenant))
        ->assertDontSee('name="expires_at"', false);

    config(['easy-auth.custom_invitation_expiration' => true]);

    $this->actingAs($admin)
        ->get(route('tenants.invitations.create', $tenant))
        ->assertSee('name="expires_at"', false);
});

test('admin can issue a reusable invitation with a maximum number of uses when multi-use invitations are enabled', function () {
    config(['easy-auth.multi_use_invitations' => true]);
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $response = $this->actingAs($admin)->post(route('tenants.invitations.store', $tenant), [
        'role' => Tenant::MEMBER_ROLE,
        'max_uses' => 3,
    ]);

    $response->assertRedirect(route('tenants.invitations.create', $tenant));

    $invitation = Invitation::where('tenant_id', $tenant->id)->first();
    expect($invitation->max_uses)->toBe(3);
});

test('admin can issue an invitation with unlimited uses by leaving max_uses blank when multi-use invitations are enabled', function () {
    config(['easy-auth.multi_use_invitations' => true]);
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $response = $this->actingAs($admin)->post(route('tenants.invitations.store', $tenant), [
        'role' => Tenant::MEMBER_ROLE,
        'max_uses' => '',
    ]);

    $response->assertRedirect(route('tenants.invitations.create', $tenant));

    $invitation = Invitation::where('tenant_id', $tenant->id)->first();
    expect($invitation->max_uses)->toBeNull();
});

test('a submitted max_uses is ignored and forced to 1 when multi-use invitations are disabled', function () {
    expect(config('easy-auth.multi_use_invitations'))->toBeFalse();
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $response = $this->actingAs($admin)->post(route('tenants.invitations.store', $tenant), [
        'role' => Tenant::MEMBER_ROLE,
        'max_uses' => 5,
    ]);

    $response->assertRedirect(route('tenants.invitations.create', $tenant));

    $invitation = Invitation::where('tenant_id', $tenant->id)->first();
    expect($invitation->max_uses)->toBe(1);
});

test('the max_uses field is hidden from the invitation form unless multi-use invitations are enabled', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $this->actingAs($admin)
        ->get(route('tenants.invitations.create', $tenant))
        ->assertDontSee('name="max_uses"', false);

    config(['easy-auth.multi_use_invitations' => true]);

    $this->actingAs($admin)
        ->get(route('tenants.invitations.create', $tenant))
        ->assertSee('name="max_uses"', false);
});

test('the uses count is hidden from the invitation list unless multi-use invitations are enabled', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);
    Invitation::factory()->for($tenant)->create();

    $this->actingAs($admin)
        ->get(route('tenants.invitations.index', $tenant))
        ->assertDontSee(__('easy-auth::invitations.uses_label'));

    config(['easy-auth.multi_use_invitations' => true]);

    $this->actingAs($admin)
        ->get(route('tenants.invitations.index', $tenant))
        ->assertSee(__('easy-auth::invitations.uses_label'));
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

test('an invitation that is both used and expired shows as used, not expired, in the invitation list', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);
    Invitation::factory()->for($tenant)->used()->expired()->create();

    $response = $this->actingAs($admin)->get(route('tenants.invitations.index', $tenant));

    $response->assertSee(__('easy-auth::invitations.status_used'));
    $response->assertDontSee(__('easy-auth::invitations.status_expired'));
});

test('a used invitation does not show a revoke button in the invitation list', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);
    Invitation::factory()->for($tenant)->used()->create();

    $response = $this->actingAs($admin)->get(route('tenants.invitations.index', $tenant));

    $response->assertSee(__('easy-auth::invitations.status_used'));
    $response->assertDontSee(__('easy-auth::invitations.revoke'));
});

test('admin can revoke an invitation', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $invitation = Invitation::factory()->for($tenant)->create();

    $response = $this->actingAs($admin)->delete(route('tenants.invitations.destroy', [$tenant, $invitation]));

    $response->assertRedirect(route('tenants.invitations.index', $tenant));
    expect($invitation->refresh()->isRevoked())->toBeTrue();
    expect($invitation->isUsable())->toBeFalse();
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
    expect($invitation->redemptions()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('a member can redeem a same-role invitation to refresh their membership', function () {
    $user = User::factory()->create(['name' => 'Existing Member']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $user, Tenant::MEMBER_ROLE);

    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->create(['token' => Invitation::hashToken($token)]);

    $this->actingAs($user)->get(route('invitations.show', $token))->assertOk();

    $response = $this->actingAs($user)->post(route('invitations.redeem', $token));

    $response->assertRedirect(route('home'));
    expect($tenant->users()->wherePivot('user_id', $user->id)->first()->pivot->role)->toBe(Tenant::MEMBER_ROLE);
    expect($invitation->refresh()->isUsed())->toBeTrue();
});

test('an admin can redeem a same-role admin invitation to refresh their membership', function () {
    $admin = User::factory()->create(['name' => 'Existing Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->admin()->create(['token' => Invitation::hashToken($token)]);

    $this->actingAs($admin)->get(route('invitations.show', $token))->assertOk();

    $response = $this->actingAs($admin)->post(route('invitations.redeem', $token));

    $response->assertRedirect(route('home'));
    expect($tenant->users()->wherePivot('user_id', $admin->id)->first()->pivot->role)->toBe(Tenant::ADMIN_ROLE);
    expect($invitation->refresh()->isUsed())->toBeTrue();
});

test('a member can redeem an admin invitation to be promoted to admin', function () {
    $member = User::factory()->create(['name' => 'Existing Member']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $member, Tenant::MEMBER_ROLE);

    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->admin()->create(['token' => Invitation::hashToken($token)]);

    $this->actingAs($member)->get(route('invitations.show', $token))->assertOk();

    $response = $this->actingAs($member)->post(route('invitations.redeem', $token));

    $response->assertRedirect(route('home'));
    expect($tenant->users()->wherePivot('user_id', $member->id)->first()->pivot->role)->toBe(Tenant::ADMIN_ROLE);
    expect($invitation->refresh()->isUsed())->toBeTrue();
});

test('an admin already in the tenant sees an already admin message without side effects when redeeming a member invitation', function () {
    $admin = User::factory()->create(['name' => 'Existing Admin']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->create(['token' => Invitation::hashToken($token)]);

    $this->actingAs($admin)->get(route('invitations.show', $token))->assertOk();

    $response = $this->actingAs($admin)->post(route('invitations.redeem', $token));

    $response->assertOk();
    expect($invitation->refresh()->isUsed())->toBeFalse();
    expect($tenant->users()->wherePivot('user_id', $admin->id)->first()->pivot->role)->toBe(Tenant::ADMIN_ROLE);
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

test('a reusable invitation can be redeemed by multiple different users up to its max uses', function () {
    $tenant = Tenant::factory()->create();
    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->reusable(2)->create([
        'token' => Invitation::hashToken($token),
    ]);

    $first = User::factory()->create();
    $second = User::factory()->create();
    $third = User::factory()->create();

    $this->actingAs($first)->post(route('invitations.redeem', $token))->assertRedirect(route('home'));
    $this->actingAs($second)->post(route('invitations.redeem', $token))->assertRedirect(route('home'));

    expect($tenant->hasMember($first))->toBeTrue();
    expect($tenant->hasMember($second))->toBeTrue();
    expect($invitation->refresh()->isUsed())->toBeTrue();

    $this->actingAs($third)->get(route('invitations.show', $token))->assertOk();
    expect($tenant->hasMember($third))->toBeFalse();
});

test('an invitation with unlimited uses can be redeemed many times', function () {
    $tenant = Tenant::factory()->create();
    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->reusable()->create([
        'token' => Invitation::hashToken($token),
    ]);

    foreach (range(1, 5) as $i) {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('invitations.redeem', $token))->assertRedirect(route('home'));
        expect($tenant->hasMember($user))->toBeTrue();
    }

    expect($invitation->refresh()->isUsed())->toBeFalse();
    expect($invitation->isUsable())->toBeTrue();
});

test('a revoked invitation cannot be redeemed even with uses remaining', function () {
    $tenant = Tenant::factory()->create();
    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->reusable(5)->create([
        'token' => Invitation::hashToken($token),
        'revoked_at' => now(),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('invitations.show', $token))->assertOk();
    expect($tenant->hasMember($user))->toBeFalse();
});

test('admin can update tenant settings including member invites toggle', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create(['member_invites_enabled' => true]);
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $response = $this->actingAs($admin)->patch(route('tenants.update', $tenant), [
        'tenant_name' => 'Renamed Tenant',
        'member_invites_enabled' => false,
    ]);

    $response->assertRedirect(route('tenants.edit', $tenant));

    $tenant->refresh();
    expect($tenant->name)->toBe('Renamed Tenant');
    expect($tenant->member_invites_enabled)->toBeFalse();
});

test('non admin cannot update tenant settings', function () {
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $member, Tenant::MEMBER_ROLE);

    $response = $this->actingAs($member)->get(route('tenants.edit', $tenant));

    $response->assertForbidden();
});

test('creating an invitation dispatches InvitationCreating and InvitationCreated', function () {
    Event::fake([InvitationCreating::class, InvitationCreated::class]);
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $this->actingAs($admin)->post(route('tenants.invitations.store', $tenant), [
        'role' => Tenant::MEMBER_ROLE,
    ]);

    Event::assertDispatched(InvitationCreating::class, fn ($event) => $event->tenant->is($tenant) && $event->user->is($admin) && $event->validated['role'] === Tenant::MEMBER_ROLE);
    Event::assertDispatched(InvitationCreated::class, fn ($event) => $event->invitation->tenant_id === $tenant->id);
});

test('revoking an invitation dispatches InvitationRevoking and InvitationRevoked', function () {
    Event::fake([InvitationRevoking::class, InvitationRevoked::class]);
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);
    $invitation = Invitation::factory()->for($tenant)->create();

    $this->actingAs($admin)->delete(route('tenants.invitations.destroy', [$tenant, $invitation]));

    Event::assertDispatched(InvitationRevoking::class, fn ($event) => $event->invitation->is($invitation));
    Event::assertDispatched(InvitationRevoked::class, fn ($event) => $event->invitation->is($invitation));
});

test('redeeming an invitation dispatches InvitationRedeeming and InvitationRedeemed', function () {
    Event::fake([InvitationRedeeming::class, InvitationRedeemed::class]);
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->create(['token' => Invitation::hashToken($token)]);

    $this->actingAs($user)->post(route('invitations.redeem', $token));

    Event::assertDispatched(InvitationRedeeming::class, fn ($event) => $event->invitation->is($invitation) && $event->user->is($user));
    Event::assertDispatched(InvitationRedeemed::class, fn ($event) => $event->invitation->is($invitation) && $event->user->is($user));
});

test('an already admin redeem attempt does not dispatch InvitationRedeeming', function () {
    Event::fake([InvitationRedeeming::class, InvitationRedeemed::class]);
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);
    $token = Invitation::generateToken();
    Invitation::factory()->for($tenant)->create(['token' => Invitation::hashToken($token)]);

    $this->actingAs($admin)->post(route('invitations.redeem', $token));

    Event::assertNotDispatched(InvitationRedeeming::class);
    Event::assertNotDispatched(InvitationRedeemed::class);
});

test('redeem-panel renders the invalid branch verbatim', function () {
    $token = Invitation::generateToken();
    $tenant = Tenant::factory()->create();
    Invitation::factory()->for($tenant)->used()->create(['token' => Invitation::hashToken($token)]);

    $response = $this->get(route('invitations.show', $token));

    $response->assertSee(__('easy-auth::invitations.invalid'));
});

test('redeem-panel renders the already_admin branch verbatim', function () {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create(['name' => 'Acme']);
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);
    $token = Invitation::generateToken();
    Invitation::factory()->for($tenant)->create(['token' => Invitation::hashToken($token)]);

    $response = $this->actingAs($admin)->get(route('invitations.show', $token));

    $response->assertSee(__('easy-auth::invitations.already_admin', ['tenant' => 'Acme']));
});

test('redeem-panel renders the already-member promotion branch verbatim', function () {
    $member = User::factory()->create();
    $tenant = Tenant::factory()->create(['name' => 'Acme']);
    attachTenantMember($tenant, $member, Tenant::MEMBER_ROLE);
    $token = Invitation::generateToken();
    Invitation::factory()->for($tenant)->admin()->create(['token' => Invitation::hashToken($token)]);

    $response = $this->actingAs($member)->get(route('invitations.show', $token));

    $response->assertSee(__('easy-auth::invitations.promote_confirm', ['tenant' => 'Acme']));
    $response->assertSee(__('easy-auth::invitations.promote_button'));
});

test('redeem-panel renders the already-member refresh branch verbatim', function () {
    $member = User::factory()->create();
    $tenant = Tenant::factory()->create(['name' => 'Acme']);
    attachTenantMember($tenant, $member, Tenant::MEMBER_ROLE);
    $token = Invitation::generateToken();
    Invitation::factory()->for($tenant)->create(['token' => Invitation::hashToken($token)]);

    $response = $this->actingAs($member)->get(route('invitations.show', $token));

    $response->assertSee(__('easy-auth::invitations.refresh_confirm', ['tenant' => 'Acme']));
    $response->assertSee(__('easy-auth::invitations.refresh_button'));
});

test('redeem-panel renders the normal join branch verbatim', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create(['name' => 'Acme']);
    $token = Invitation::generateToken();
    Invitation::factory()->for($tenant)->create(['token' => Invitation::hashToken($token)]);

    $response = $this->actingAs($user)->get(route('invitations.show', $token));

    $response->assertSee(__('easy-auth::invitations.join_prompt', ['tenant' => 'Acme', 'role' => __('easy-auth::invitations.role_member')]));
    $response->assertSee(__('easy-auth::invitations.join_button'));
});

test('invitation create-form renders the copy-to-clipboard-button component when a URL was just issued', function () {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);

    $this->actingAs($admin)->post(route('tenants.invitations.store', $tenant), [
        'role' => Tenant::MEMBER_ROLE,
    ]);

    $response = $this->actingAs($admin)->get(route('tenants.invitations.create', $tenant));

    $response->assertOk();
    $response->assertSee('js-copy-to-clipboard-button', false);
});

test('an app can extend an invitation row with the invitation-row-actions include-if slot', function () {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);
    $invitation = Invitation::factory()->for($tenant)->create();

    $this->app['view']->addLocation(__DIR__.'/../Fixtures/optional-views');

    $response = $this->actingAs($admin)->get(route('tenants.invitations.index', $tenant));

    $response->assertSee("extra-actions-for-invitation-{$invitation->id}", false);
});

test('the invitation-row-actions slot renders nothing when the app has not published one', function () {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachTenantMember($tenant, $admin, Tenant::ADMIN_ROLE);
    Invitation::factory()->for($tenant)->create();

    $response = $this->actingAs($admin)->get(route('tenants.invitations.index', $tenant));

    $response->assertDontSee('extra-actions-for-invitation-', false);
});

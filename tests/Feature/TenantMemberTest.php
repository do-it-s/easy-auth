<?php

use DoITs\EasyAuth\Events\TenantMemberRemoved;
use DoITs\EasyAuth\Events\TenantMemberRemoving;
use DoITs\EasyAuth\Events\TenantMemberRoleUpdated;
use DoITs\EasyAuth\Events\TenantMemberRoleUpdating;
use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Support\Facades\Event;

test('admin can view the member list', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $member = User::factory()->create(['name' => 'Taro']);
    $tenant = Tenant::factory()->create();

    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()->subDay()]);

    $response = $this->actingAs($admin)->get(route('tenants.members.index', $tenant));

    $response->assertOk();
    $response->assertSee('Admin');
    $response->assertSee('Taro');
});

test('ordinary member cannot view the member list', function () {
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($member)->get(route('tenants.members.index', $tenant))->assertForbidden();
});

test('non-member cannot view the member list', function () {
    $outsider = User::factory()->create(['name' => 'Outsider']);
    $tenant = Tenant::factory()->create();

    $this->actingAs($outsider)->get(route('tenants.members.index', $tenant))->assertForbidden();
});

test('member list shows admins in the first section and others in the second', function () {
    $admin = User::factory()->create(['name' => 'Hanako']);
    $member = User::factory()->create(['name' => 'Taro']);
    $tenant = Tenant::factory()->create();

    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->get(route('tenants.members.index', $tenant));

    $response->assertSeeInOrder(['Hanako', 'Taro']);
});

test('member list is not paginated by default', function () {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    foreach (User::factory()->count(3)->create() as $member) {
        $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);
    }

    $response = $this->actingAs($admin)->get(route('tenants.members.index', $tenant));

    $response->assertViewHas('others', fn ($others) => $others->count() === 3);
});

test('member list paginates the admin section independently when configured', function () {
    config(['easy-auth.members_admins_per_page' => 1]);
    $admin1 = User::factory()->create();
    $admin2 = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin1, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($admin2, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin1)->get(route('tenants.members.index', $tenant));

    $response->assertViewHas('admins', fn ($admins) => $admins->count() === 1 && $admins->total() === 2);
    $response->assertViewHas('adminCount', 2);
});

test('member list paginates the non-admin section independently when configured', function () {
    config(['easy-auth.members_others_per_page' => 1]);
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    foreach (User::factory()->count(2)->create() as $member) {
        $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);
    }

    $response = $this->actingAs($admin)->get(route('tenants.members.index', $tenant));

    $response->assertViewHas('others', fn ($others) => $others->count() === 1 && $others->total() === 2);
    $response->assertViewHas('admins', fn ($admins) => $admins->count() === 1 && $admins->total() === 1);
});

test('member row shows promote and remove buttons for a member visible to an admin', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $member = User::factory()->create(['name' => 'Taro']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->get(route('tenants.members.index', $tenant));

    $response->assertSee(__('easy-auth::members.promote'));
    $response->assertSee(__('easy-auth::members.remove'));
});

test('member row hides management buttons for the sole admin viewing themselves', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->get(route('tenants.members.index', $tenant));

    $response->assertDontSee(__('easy-auth::members.demote'));
    $response->assertDontSee(__('easy-auth::members.remove'));
});

test('an app can extend a member row with the member-row-actions include-if slot', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $member = User::factory()->create(['name' => 'Taro']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $this->app['view']->addLocation(__DIR__.'/../Fixtures/optional-views');

    $response = $this->actingAs($admin)->get(route('tenants.members.index', $tenant));

    $response->assertSee("extra-actions-for-{$member->id}-member", false);
});

test('the member-row-actions slot renders nothing when the app has not published one', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $member = User::factory()->create(['name' => 'Taro']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->get(route('tenants.members.index', $tenant));

    $response->assertDontSee('extra-actions-for-', false);
});

test('admin can promote a member to admin', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create();

    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->patch(route('tenants.members.update', [$tenant, $member]), [
        'role' => Tenant::ADMIN_ROLE,
    ]);

    $response->assertRedirect(route('tenants.members.index', $tenant));
    expect($tenant->users()->wherePivot('user_id', $member->id)->first()->pivot->role)->toBe(Tenant::ADMIN_ROLE);
});

test('admin cannot demote themselves', function () {
    $admin1 = User::factory()->create(['name' => 'Admin1']);
    $admin2 = User::factory()->create(['name' => 'Admin2']);
    $tenant = Tenant::factory()->create();

    $tenant->users()->attach($admin1, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($admin2, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($admin1)->patch(route('tenants.members.update', [$tenant, $admin1]), [
        'role' => Tenant::MEMBER_ROLE,
    ])->assertForbidden();

    expect($tenant->users()->wherePivot('user_id', $admin1->id)->first()->pivot->role)->toBe(Tenant::ADMIN_ROLE);
});

test('admin can demote another admin when other admins exist', function () {
    $admin1 = User::factory()->create(['name' => 'Admin1']);
    $admin2 = User::factory()->create(['name' => 'Admin2']);
    $tenant = Tenant::factory()->create();

    $tenant->users()->attach($admin1, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($admin2, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin1)->patch(route('tenants.members.update', [$tenant, $admin2]), [
        'role' => Tenant::MEMBER_ROLE,
    ]);

    $response->assertRedirect(route('tenants.members.index', $tenant));
    expect($tenant->users()->wherePivot('user_id', $admin2->id)->first()->pivot->role)->toBe(Tenant::MEMBER_ROLE);
});

test('cannot demote the last admin', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($admin)->patch(route('tenants.members.update', [$tenant, $admin]), [
        'role' => Tenant::MEMBER_ROLE,
    ])->assertForbidden();

    expect($tenant->users()->wherePivot('user_id', $admin->id)->first()->pivot->role)->toBe(Tenant::ADMIN_ROLE);
});

test('promoting a member dispatches TenantMemberRoleUpdating and TenantMemberRoleUpdated', function () {
    Event::fake([TenantMemberRoleUpdating::class, TenantMemberRoleUpdated::class]);
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($admin)->patch(route('tenants.members.update', [$tenant, $member]), [
        'role' => Tenant::ADMIN_ROLE,
    ]);

    Event::assertDispatched(TenantMemberRoleUpdating::class, fn ($event) => $event->tenant->is($tenant) && $event->member->is($member) && $event->role === Tenant::ADMIN_ROLE);
    Event::assertDispatched(TenantMemberRoleUpdated::class, fn ($event) => $event->tenant->is($tenant) && $event->member->is($member) && $event->role === Tenant::ADMIN_ROLE);
});

test('member cannot change roles', function () {
    $member = User::factory()->create(['name' => 'Member']);
    $other = User::factory()->create(['name' => 'Another Member']);
    $tenant = Tenant::factory()->create();

    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($other, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($member)->patch(route('tenants.members.update', [$tenant, $other]), [
        'role' => Tenant::ADMIN_ROLE,
    ])->assertForbidden();
});

test('non-member cannot change roles', function () {
    $outsider = User::factory()->create(['name' => 'Outsider']);
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($outsider)->patch(route('tenants.members.update', [$tenant, $member]), [
        'role' => Tenant::ADMIN_ROLE,
    ])->assertForbidden();
});

test('admin cannot remove themselves', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($admin)->delete(route('tenants.members.destroy', [$tenant, $admin]))
        ->assertForbidden();
});

test('admin can remove a member', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create();

    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->delete(route('tenants.members.destroy', [$tenant, $member]));

    $response->assertRedirect(route('tenants.members.index', $tenant));
    expect($tenant->hasMember($member))->toBeFalse();
});

test('admin can remove another admin when other admins exist', function () {
    $admin1 = User::factory()->create(['name' => 'Admin1']);
    $admin2 = User::factory()->create(['name' => 'Admin2']);
    $tenant = Tenant::factory()->create();

    $tenant->users()->attach($admin1, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($admin2, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin1)->delete(route('tenants.members.destroy', [$tenant, $admin2]));

    $response->assertRedirect(route('tenants.members.index', $tenant));
    expect($tenant->hasMember($admin2))->toBeFalse();
});

test('cannot remove the last admin', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($admin)->delete(route('tenants.members.destroy', [$tenant, $admin]))
        ->assertForbidden();

    expect($tenant->hasMember($admin))->toBeTrue();
});

test('removing a member dispatches TenantMemberRemoving and TenantMemberRemoved', function () {
    Event::fake([TenantMemberRemoving::class, TenantMemberRemoved::class]);
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($admin)->delete(route('tenants.members.destroy', [$tenant, $member]));

    Event::assertDispatched(TenantMemberRemoving::class, fn ($event) => $event->tenant->is($tenant) && $event->member->is($member));
    Event::assertDispatched(TenantMemberRemoved::class, fn ($event) => $event->tenant->is($tenant) && $event->member->is($member));
});

test('member cannot remove other members', function () {
    $member = User::factory()->create(['name' => 'Member']);
    $other = User::factory()->create(['name' => 'Another Member']);
    $tenant = Tenant::factory()->create();

    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($other, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($member)->delete(route('tenants.members.destroy', [$tenant, $other]))
        ->assertForbidden();
});

test('non-member cannot remove a member', function () {
    $outsider = User::factory()->create(['name' => 'Outsider']);
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($outsider)->delete(route('tenants.members.destroy', [$tenant, $member]))
        ->assertForbidden();
});

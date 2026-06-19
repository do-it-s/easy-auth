<?php

use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Tests\Fixtures\User;

test('profile edit page shows the leave section for the current tenant', function () {
    $user = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant']);
    $tenant->users()->attach($user, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk();
    $response->assertSee('Type your name to leave Current Tenant');
});

test('profile edit page hides the leave section when the user has no tenant', function () {
    $user = User::factory()->create(['name' => 'Member']);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk();
    $response->assertDontSee('Type your name to leave', false);
});

test('member can leave the tenant when the typed name matches', function () {
    $user = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($user, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($user)->delete(route('tenants.members.leave', $tenant), [
        'name' => 'Member',
    ]);

    $response->assertRedirect(route('profile.edit'));
    expect($tenant->hasMember($user))->toBeFalse();
});

test('member cannot leave when the typed name does not match', function () {
    $user = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($user, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($user)->delete(route('tenants.members.leave', $tenant), [
        'name' => 'Wrong Name',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('name', null, 'leaveTenant');
    expect($tenant->hasMember($user))->toBeTrue();
});

test('member cannot leave when the typed name is blank', function () {
    $user = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($user, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($user)->delete(route('tenants.members.leave', $tenant), [
        'name' => '',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('name', null, 'leaveTenant');
    expect($tenant->hasMember($user))->toBeTrue();
});

test('admin can leave when other admins exist', function () {
    $admin1 = User::factory()->create(['name' => 'Admin1']);
    $admin2 = User::factory()->create(['name' => 'Admin2']);
    $tenant = Tenant::factory()->create();

    $tenant->users()->attach($admin1, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($admin2, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin1)->delete(route('tenants.members.leave', $tenant), [
        'name' => 'Admin1',
    ]);

    $response->assertRedirect(route('profile.edit'));
    expect($tenant->hasMember($admin1))->toBeFalse();
});

test('last admin cannot leave', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->delete(route('tenants.members.leave', $tenant), [
        'name' => 'Admin',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('name', null, 'leaveTenant');
    expect($tenant->hasMember($admin))->toBeTrue();
});

test('non-member cannot leave a tenant', function () {
    $outsider = User::factory()->create(['name' => 'Outsider']);
    $tenant = Tenant::factory()->create();

    $this->actingAs($outsider)->delete(route('tenants.members.leave', $tenant), [
        'name' => 'Outsider',
    ])->assertForbidden();
});

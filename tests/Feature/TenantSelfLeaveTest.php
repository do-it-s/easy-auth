<?php

use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Tests\Fixtures\User;

test('leave confirmation page is shown to a member', function () {
    $user = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant']);
    $tenant->users()->attach($user, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($user)->get(route('tenants.leave.show', $tenant));

    $response->assertOk();
    $response->assertSee('Type Current Tenant to confirm you want to leave.');
});

test('non-member cannot view the leave confirmation page', function () {
    $outsider = User::factory()->create(['name' => 'Outsider']);
    $tenant = Tenant::factory()->create();

    $this->actingAs($outsider)->get(route('tenants.leave.show', $tenant))
        ->assertForbidden();
});

test('member can leave the tenant when the typed tenant name matches', function () {
    $user = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant']);
    $tenant->users()->attach($user, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($user)->delete(route('tenants.leave', $tenant), [
        'name' => 'Current Tenant',
    ]);

    $response->assertRedirect(route('home'));
    expect($tenant->hasMember($user))->toBeFalse();
});

test('member cannot leave when the typed tenant name does not match', function () {
    $user = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant']);
    $tenant->users()->attach($user, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($user)->delete(route('tenants.leave', $tenant), [
        'name' => 'Wrong Name',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('name');
    expect($tenant->hasMember($user))->toBeTrue();
});

test('member cannot leave when the typed tenant name is blank', function () {
    $user = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($user, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($user)->delete(route('tenants.leave', $tenant), [
        'name' => '',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('name');
    expect($tenant->hasMember($user))->toBeTrue();
});

test('admin can leave when other admins exist', function () {
    $admin1 = User::factory()->create(['name' => 'Admin1']);
    $admin2 = User::factory()->create(['name' => 'Admin2']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant']);

    $tenant->users()->attach($admin1, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($admin2, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin1)->delete(route('tenants.leave', $tenant), [
        'name' => 'Current Tenant',
    ]);

    $response->assertRedirect(route('home'));
    expect($tenant->hasMember($admin1))->toBeFalse();
});

test('last admin cannot leave', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant']);
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->delete(route('tenants.leave', $tenant), [
        'name' => 'Current Tenant',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('name');
    expect($tenant->hasMember($admin))->toBeTrue();
});

test('non-member cannot leave a tenant', function () {
    $outsider = User::factory()->create(['name' => 'Outsider']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant']);

    $this->actingAs($outsider)->delete(route('tenants.leave', $tenant), [
        'name' => 'Current Tenant',
    ])->assertForbidden();
});

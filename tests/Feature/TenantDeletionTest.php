<?php

use DoITs\EasyAuth\Models\Invitation;
use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Tests\Fixtures\User;

test('delete confirmation page is shown to an admin', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant']);
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->get(route('tenants.delete.show', $tenant));

    $response->assertOk();
    $response->assertSee('Current Tenant');
});

test('a member cannot view the delete confirmation page', function () {
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($member)->get(route('tenants.delete.show', $tenant))
        ->assertForbidden();
});

test('a non-member cannot view the delete confirmation page', function () {
    $outsider = User::factory()->create(['name' => 'Outsider']);
    $tenant = Tenant::factory()->create();

    $this->actingAs($outsider)->get(route('tenants.delete.show', $tenant))
        ->assertForbidden();
});

test('an admin can delete the tenant when the typed tenant name matches, cascading cleanly', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant']);
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);
    $invitation = Invitation::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($admin)->delete(route('tenants.delete', $tenant), [
        'tenant_name' => 'Current Tenant',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    $this->assertDatabaseMissing('tenant_user', ['tenant_id' => $tenant->id]);
    $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
});

test('the sole admin can delete the tenant', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant']);
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->delete(route('tenants.delete', $tenant), [
        'tenant_name' => 'Current Tenant',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
});

test('an admin cannot delete the tenant when the typed tenant name does not match', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant']);
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->delete(route('tenants.delete', $tenant), [
        'tenant_name' => 'Wrong Name',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('tenant_name');
    $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
});

test('an admin cannot delete the tenant when the typed tenant name is blank', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->delete(route('tenants.delete', $tenant), [
        'tenant_name' => '',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('tenant_name');
    $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
});

test('a member cannot delete the tenant', function () {
    $member = User::factory()->create(['name' => 'Member']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant']);
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($member)->delete(route('tenants.delete', $tenant), [
        'tenant_name' => 'Current Tenant',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
});

test('a non-member cannot delete the tenant', function () {
    $outsider = User::factory()->create(['name' => 'Outsider']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant']);

    $response = $this->actingAs($outsider)->delete(route('tenants.delete', $tenant), [
        'tenant_name' => 'Current Tenant',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
});

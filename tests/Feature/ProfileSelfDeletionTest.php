<?php

use DoITs\EasyAuth\Events\AccountDeleted;
use DoITs\EasyAuth\Events\AccountDeleting;
use DoITs\EasyAuth\Models\Invitation;
use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('delete confirmation page is shown to a non-admin user', function () {
    $user = User::factory()->create(['name' => 'Taro']);

    $response = $this->actingAs($user)->get(route('profile.delete.show'));

    $response->assertOk();
    $response->assertSee('Taro');
    $response->assertSee('id="name"', false);
});

test('a user with no tenants can view the delete confirmation page', function () {
    $user = User::factory()->create(['name' => 'Taro']);

    $this->actingAs($user)->get(route('profile.delete.show'))->assertOk();
});

test('a tenant admin cannot view the delete confirmation page', function () {
    $admin1 = User::factory()->create(['name' => 'Admin1']);
    $admin2 = User::factory()->create(['name' => 'Admin2']);
    $tenant = Tenant::factory()->create();

    $tenant->users()->attach($admin1, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($admin2, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($admin1)->get(route('profile.delete.show'))->assertForbidden();
});

test('a non-admin member can delete their own account, cascading cleanly', function () {
    $user = User::factory()->create(['name' => 'Taro']);
    $device = $user->device()->create(['uuid' => (string) Str::uuid()]);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($user, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);
    $invitation = Invitation::factory()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)->delete(route('profile.delete'), ['name' => 'Taro']);

    $response->assertRedirect(route('account-deletion.deleted'));
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertDatabaseMissing('devices', ['id' => $device->id]);
    $this->assertDatabaseMissing('tenant_user', ['user_id' => $user->id]);
    $this->assertDatabaseHas('invitations', ['id' => $invitation->id, 'created_by' => null]);
});

test('a user with no tenants can delete their own account', function () {
    $user = User::factory()->create(['name' => 'Taro']);

    $response = $this->actingAs($user)->delete(route('profile.delete'), ['name' => 'Taro']);

    $response->assertRedirect(route('account-deletion.deleted'));
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('a tenant admin cannot delete their own account even when other admins exist', function () {
    $admin1 = User::factory()->create(['name' => 'Admin1']);
    $admin2 = User::factory()->create(['name' => 'Admin2']);
    $tenant = Tenant::factory()->create();

    $tenant->users()->attach($admin1, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($admin2, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin1)->delete(route('profile.delete'), ['name' => 'Admin1']);

    $response->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $admin1->id]);
});

test('the sole admin of a tenant cannot delete their own account', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->delete(route('profile.delete'), ['name' => 'Admin']);

    $response->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('confirming with the wrong name does not delete the account', function () {
    $user = User::factory()->create(['name' => 'Taro']);

    $response = $this->actingAs($user)->delete(route('profile.delete'), ['name' => 'Wrong Name']);

    $response->assertRedirect();
    $response->assertSessionHasErrors('name');
    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

test('a non-admin member deleting their own account dispatches AccountDeleting and AccountDeleted', function () {
    Event::fake([AccountDeleting::class, AccountDeleted::class]);
    $user = User::factory()->create(['name' => 'Taro']);

    $this->actingAs($user)->delete(route('profile.delete'), ['name' => 'Taro']);

    Event::assertDispatched(AccountDeleting::class, fn ($event) => $event->user->is($user));
    Event::assertDispatched(AccountDeleted::class, fn ($event) => $event->user->is($user));
});

test('confirming with the wrong name does not dispatch AccountDeleting or AccountDeleted', function () {
    Event::fake([AccountDeleting::class, AccountDeleted::class]);
    $user = User::factory()->create(['name' => 'Taro']);

    $this->actingAs($user)->delete(route('profile.delete'), ['name' => 'Wrong Name']);

    Event::assertNotDispatched(AccountDeleting::class);
    Event::assertNotDispatched(AccountDeleted::class);
});

test('confirming with a blank name does not delete the account', function () {
    $user = User::factory()->create(['name' => 'Taro']);

    $response = $this->actingAs($user)->delete(route('profile.delete'), ['name' => '']);

    $response->assertRedirect();
    $response->assertSessionHasErrors('name');
    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

<?php

use DoITs\EasyAuth\Events\TenantUpdated;
use DoITs\EasyAuth\Events\TenantUpdating;
use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

test('the edit page renders the edit-form component with the current name and toggle prefilled', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create(['name' => 'Current Tenant', 'member_invites_enabled' => true]);
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->get(route('tenants.edit', $tenant));

    $response->assertOk();
    $response->assertSee('value="Current Tenant"', false);
    $response->assertSee('name="member_invites_enabled"', false);
});

test('a member cannot view the edit page', function () {
    $member = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($member)->get(route('tenants.edit', $tenant))->assertForbidden();
});

test('updating a tenant dispatches TenantUpdating (with the raw request) and TenantUpdated', function () {
    Event::fake([TenantUpdating::class, TenantUpdated::class]);
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create(['name' => 'Old Name']);
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($admin)->patch(route('tenants.update', $tenant), [
        'tenant_name' => 'New Name',
        'member_invites_enabled' => '1',
    ]);

    Event::assertDispatched(TenantUpdating::class, function ($event) use ($tenant) {
        return $event->tenant->is($tenant)
            && $event->request instanceof Request
            && $event->request->boolean('member_invites_enabled') === true;
    });
    Event::assertDispatched(TenantUpdated::class, fn ($event) => $event->tenant->is($tenant) && $event->tenant->name === 'New Name');
});

test('updating a tenant flashes a status message', function () {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create(['name' => 'Old Name']);
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $response = $this->actingAs($admin)->patch(route('tenants.update', $tenant), [
        'tenant_name' => 'New Name',
    ]);

    $response->assertRedirect(route('tenants.edit', $tenant));
    $response->assertSessionHas('status');
});

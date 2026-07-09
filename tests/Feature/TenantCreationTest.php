<?php

use DoITs\EasyAuth\Events\TenantCreated;
use DoITs\EasyAuth\Events\TenantCreating;
use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Support\Facades\Event;

test('the create page renders the create-form component', function () {
    $user = User::factory()->create(['name' => 'Taro']);

    $response = $this->actingAs($user)->get(route('tenants.create'));

    $response->assertOk();
    $response->assertSee('id="tenant_name"', false);
    $response->assertSee(route('tenants.store'), false);
});

test('creating a tenant dispatches TenantCreating and TenantCreated', function () {
    Event::fake([TenantCreating::class, TenantCreated::class]);
    $user = User::factory()->create(['name' => 'Taro']);

    $this->actingAs($user)->post(route('tenants.store'), [
        'tenant_name' => 'New Tenant',
    ]);

    $tenant = Tenant::where('name', 'New Tenant')->first();

    Event::assertDispatched(TenantCreating::class, fn ($event) => $event->user->is($user) && $event->validated === ['tenant_name' => 'New Tenant']);
    Event::assertDispatched(TenantCreated::class, fn ($event) => $event->tenant->is($tenant) && $event->user->is($user));
});

<?php

use App\Models\Tenant;
use App\Models\User;

function attachMember(Tenant $tenant, User $user, ?DateTimeInterface $lastAccessedAt = null): void
{
    $tenant->users()->attach($user, [
        'role' => Tenant::MEMBER_ROLE,
        'last_accessed_at' => $lastAccessedAt ?? now(),
    ]);
}

test('a member can switch their current tenant', function () {
    $user = User::factory()->create();
    $previous = Tenant::factory()->create();
    $next = Tenant::factory()->create();

    attachMember($previous, $user, now()->subDay());
    attachMember($next, $user, now()->subWeek());

    expect($user->currentTenant()->id)->toBe($previous->id);

    $response = $this->actingAs($user)->post(route('tenants.switch', $next));

    $response->assertRedirect(route('home'));
    expect($user->currentTenant()->id)->toBe($next->id);
});

test('a user cannot switch to a tenant they do not belong to', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    $this->actingAs($user)->post(route('tenants.switch', $tenant))->assertForbidden();
});

test('the current tenant is the one most recently accessed', function () {
    $user = User::factory()->create();
    $older = Tenant::factory()->create();
    $newer = Tenant::factory()->create();

    attachMember($older, $user, now()->subDay());
    attachMember($newer, $user, now());

    expect($user->currentTenant()->id)->toBe($newer->id);
});

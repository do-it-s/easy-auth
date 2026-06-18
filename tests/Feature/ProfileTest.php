<?php

use DoITs\EasyAuth\Tests\Fixtures\User;

test('guests visiting tenant creation are redirected to the registration page', function () {
    $response = $this->get('/tenants/create');

    $response->assertRedirect(route('profile.create'));
});

test('authenticated users with an incomplete profile are redirected to the profile page', function () {
    $user = User::factory()->create(['name' => '']);

    $response = $this->actingAs($user)->get('/tenants/create');

    $response->assertRedirect(route('profile.edit'));
});

test('authenticated users with a complete profile can access tenant creation', function () {
    $user = User::factory()->create(['name' => 'Taro']);

    $response = $this->actingAs($user)->get('/tenants/create');

    $response->assertOk();
});

test('completing the profile redirects back to the originally intended page', function () {
    $user = User::factory()->create(['name' => '']);

    $this->actingAs($user)->get('/tenants/create');

    $response = $this->actingAs($user)->patch('/profile', ['name' => 'Taro']);

    $response->assertRedirect('/tenants/create');
    expect($user->refresh()->name)->toBe('Taro');
});

test('completing the profile via json returns the intended redirect target', function () {
    $user = User::factory()->create(['name' => '']);

    $this->actingAs($user)->get('/tenants/create');

    $response = $this->actingAs($user)->patchJson('/profile', ['name' => 'Taro']);

    $response->assertOk()->assertJson(['redirect' => url('/tenants/create')]);
    expect($user->refresh()->name)->toBe('Taro');
});

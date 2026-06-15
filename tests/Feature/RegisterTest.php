<?php

use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;

test('a user can register with an email and password and is logged in', function () {
    $response = $this->postJson('/profile-password', [
        'name' => 'Taro',
        'email' => 'taro@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertOk()->assertJson(['redirect' => url('/')]);

    $user = User::where('email', 'taro@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Taro');
    $this->assertAuthenticatedAs($user);
});

test('registering with an email and password redeems a pending invitation', function () {
    $tenant = Tenant::factory()->create();
    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->create([
        'token' => Invitation::hashToken($token),
    ]);

    $this->withSession(['pending_invitation_token' => $token])->postJson('/profile-password', [
        'name' => 'Taro',
        'email' => 'taro@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertOk();

    $user = User::where('email', 'taro@example.com')->first();

    expect($user->tenants)->toHaveCount(1);
    expect($user->tenants->first()->id)->toBe($tenant->id);
    expect($invitation->refresh()->isUsed())->toBeTrue();
});

test('registering with an email that is already taken fails validation', function () {
    User::factory()->create(['email' => 'taro@example.com']);

    $response = $this->postJson('/profile-password', [
        'name' => 'Taro',
        'email' => 'taro@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('registering with mismatched password confirmation fails validation', function () {
    $response = $this->postJson('/profile-password', [
        'name' => 'Taro',
        'email' => 'taro@example.com',
        'password' => 'password',
        'password_confirmation' => 'different',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('password');
});

test('authenticated users cannot access the registration endpoint', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/profile-password', [
        'name' => 'Taro',
        'email' => 'taro@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('home'));
});

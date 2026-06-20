<?php

use DoITs\EasyAuth\Notifications\AccountDeletionLinkNotification;
use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

test('a user can log in with the correct email and password', function () {
    $user = User::factory()->create([
        'email' => 'taro@example.com',
        'password' => 'password',
    ]);

    $response = $this->post('/login', [
        'email' => 'taro@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

test('a user is redirected to the page they originally intended after logging in', function () {
    $user = User::factory()->create([
        'name' => 'Taro',
        'email' => 'taro@example.com',
        'password' => 'password',
    ]);

    $this->get('/tenants/create');

    $response = $this->post('/login', [
        'email' => 'taro@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/tenants/create');
    $this->assertAuthenticatedAs($user);
});

test('login fails with an incorrect password', function () {
    User::factory()->create([
        'email' => 'taro@example.com',
        'password' => 'password',
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => 'taro@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('authenticated users are redirected away from the login page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/login');

    $response->assertRedirect(route('home'));
});

test('a user with a registered device can log in with the matching device UUID', function () {
    $user = User::factory()->create([
        'email' => 'taro@example.com',
        'password' => 'password',
    ]);
    $device = $user->device()->create(['uuid' => (string) Str::uuid()]);

    $response = $this->postJson('/login', [
        'email' => 'taro@example.com',
        'password' => 'password',
    ], ['X-Device-Uuid' => $device->uuid]);

    $response->assertOk();
    $this->assertAuthenticatedAs($user);
});

test('a user with a registered device cannot log in from a different device, and is emailed an account deletion link', function () {
    Notification::fake();
    $user = User::factory()->create([
        'email' => 'taro@example.com',
        'password' => 'password',
    ]);
    $user->device()->create(['uuid' => (string) Str::uuid()]);

    $response = $this->postJson('/login', [
        'email' => 'taro@example.com',
        'password' => 'password',
    ], ['X-Device-Uuid' => (string) Str::uuid()]);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
    $this->assertGuest();
    Notification::assertSentTo($user, AccountDeletionLinkNotification::class);
});

test('the device-mismatch response is indistinguishable from a wrong-password response', function () {
    Notification::fake();
    User::factory()->create([
        'email' => 'taro@example.com',
        'password' => 'password',
    ])->device()->create(['uuid' => (string) Str::uuid()]);

    $wrongPassword = $this->postJson('/login', [
        'email' => 'taro@example.com',
        'password' => 'wrong-password',
    ]);

    $wrongDevice = $this->postJson('/login', [
        'email' => 'taro@example.com',
        'password' => 'password',
    ], ['X-Device-Uuid' => (string) Str::uuid()]);

    expect($wrongDevice->status())->toBe($wrongPassword->status());
    expect($wrongDevice->json())->toEqual($wrongPassword->json());
});

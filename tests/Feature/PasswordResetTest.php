<?php

use DoITs\EasyAuth\Events\PasswordResetCompleted;
use DoITs\EasyAuth\Events\PasswordResetting;
use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

test('the password request page renders the password-request-form component with its expected elements', function () {
    $response = $this->get('/password/request');

    $response->assertOk();
    $response->assertSee('id="email"', false);
    $response->assertSee(route('password.email'), false);
});

test('the password reset page renders the password-reset-form component with its expected elements', function () {
    $response = $this->get('/password/reset/some-token');

    $response->assertOk();
    $response->assertSee('id="email"', false);
    $response->assertSee('id="password"', false);
    $response->assertSee('id="password_confirmation"', false);
    $response->assertSee('value="some-token"', false);
});

test('requesting a reset link for a fallback user sends a notification with a generic response', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'taro@example.com', 'password' => 'password']);

    $response = $this->post('/password/email', ['email' => 'taro@example.com']);

    $response->assertSessionHas('status', __('easy-auth::password_reset.link_sent'));
    Notification::assertSentTo($user, ResetPassword::class);
});

test('requesting a reset link for a passkey-only user sends nothing but returns the same response', function () {
    Notification::fake();
    User::factory()->create(['email' => 'taro@example.com', 'password' => null]);

    $response = $this->post('/password/email', ['email' => 'taro@example.com']);

    $response->assertSessionHas('status', __('easy-auth::password_reset.link_sent'));
    Notification::assertNothingSent();
});

test('requesting a reset link for an unknown email returns the same response', function () {
    Notification::fake();

    $response = $this->post('/password/email', ['email' => 'nobody@example.com']);

    $response->assertSessionHas('status', __('easy-auth::password_reset.link_sent'));
    Notification::assertNothingSent();
});

test('completing a reset dispatches PasswordResetting and PasswordResetCompleted', function () {
    Notification::fake();
    Event::fake([PasswordResetting::class, PasswordResetCompleted::class]);
    $user = User::factory()->create(['email' => 'taro@example.com', 'password' => 'old-password']);

    $this->post('/password/email', ['email' => 'taro@example.com']);

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $this->post('/password/reset', [
        'token' => $token,
        'email' => 'taro@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    Event::assertDispatched(PasswordResetting::class, fn ($event) => $event->user->is($user) && $event->password === 'new-password');
    Event::assertDispatched(PasswordResetCompleted::class, fn ($event) => $event->user->is($user));
});

test('completing a reset updates the password without authenticating, and the device check still applies afterward', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'taro@example.com', 'password' => 'old-password']);
    $device = $user->device()->create(['uuid' => (string) Str::uuid()]);

    $this->post('/password/email', ['email' => 'taro@example.com']);

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $response = $this->post('/password/reset', [
        'token' => $token,
        'email' => 'taro@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertGuest();

    $wrongDeviceResponse = $this->postJson('/login', [
        'email' => 'taro@example.com',
        'password' => 'new-password',
    ], ['X-Device-Uuid' => (string) Str::uuid()]);

    $wrongDeviceResponse->assertUnprocessable()->assertJsonValidationErrors('email');
    $this->assertGuest();

    $matchingDeviceResponse = $this->postJson('/login', [
        'email' => 'taro@example.com',
        'password' => 'new-password',
    ], ['X-Device-Uuid' => $device->uuid]);

    $matchingDeviceResponse->assertOk();
    $this->assertAuthenticatedAs($user);
});

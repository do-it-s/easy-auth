<?php

use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Support\Str;

test('reset link visibility is false when no device uuid header is sent', function () {
    $response = $this->getJson('/login/reset-link-visibility');

    $response->assertOk()->assertJson(['show_reset_link' => false]);
});

test('reset link visibility is true when the device uuid is not registered', function () {
    $response = $this->getJson('/login/reset-link-visibility', [
        'X-Device-Uuid' => (string) Str::uuid(),
    ]);

    $response->assertOk()->assertJson(['show_reset_link' => true]);
});

test('reset link visibility is true when the matched user has no password', function () {
    $user = User::factory()->create(['password' => null]);
    $device = $user->device()->create(['uuid' => (string) Str::uuid()]);

    $response = $this->getJson('/login/reset-link-visibility', [
        'X-Device-Uuid' => $device->uuid,
    ]);

    $response->assertOk()->assertJson(['show_reset_link' => true]);
});

test('reset link visibility is false when the matched user has a password', function () {
    $user = User::factory()->create(['password' => 'password']);
    $device = $user->device()->create(['uuid' => (string) Str::uuid()]);

    $response = $this->getJson('/login/reset-link-visibility', [
        'X-Device-Uuid' => $device->uuid,
    ]);

    $response->assertOk()->assertJson(['show_reset_link' => false]);
});

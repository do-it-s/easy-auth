<?php

use DoITs\EasyAuth\Events\ProfileUpdated;
use DoITs\EasyAuth\Events\ProfileUpdating;
use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Support\Facades\Event;

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

test('the registration page shows the already-registered gate markup when a pending invitation is in session', function () {
    $response = $this->withSession(['pending_invitation_token' => 'some-token'])->get(route('profile.create'));

    $response->assertOk();
    $response->assertSee('id="already-registered-notice"', false);
});

test('the registration page omits the already-registered gate markup with no pending invitation', function () {
    $response = $this->get(route('profile.create'));

    $response->assertOk();
    $response->assertDontSee('id="already-registered-notice"', false);
});

test('completing the profile honors a redeemed invitation over a stale intended url', function () {
    $user = User::factory()->create(['name' => '']);

    $this->actingAs($user)->get('/tenants/create'); // sets a stale url.intended via EnsureProfileIsComplete

    $response = $this->withSession(['post_registration_redirect' => route('home')])
        ->actingAs($user)
        ->patchJson('/profile', ['name' => 'Taro']);

    $response->assertOk()->assertJson(['redirect' => url('/')]);
});

test('the edit page renders the edit-form component with the current name prefilled', function () {
    $user = User::factory()->create(['name' => 'Taro']);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk();
    $response->assertSee('id="name"', false);
    $response->assertSee('value="Taro"', false);
});

test('updating the profile dispatches ProfileUpdating and ProfileUpdated', function () {
    Event::fake([ProfileUpdating::class, ProfileUpdated::class]);
    $user = User::factory()->create(['name' => '']);

    $this->actingAs($user)->patch('/profile', ['name' => 'Taro']);

    Event::assertDispatched(ProfileUpdating::class, fn ($event) => $event->user->is($user) && $event->validated === ['name' => 'Taro']);
    Event::assertDispatched(ProfileUpdated::class, fn ($event) => $event->user->is($user));
});

test('updating the profile flashes a status message', function () {
    $user = User::factory()->create(['name' => 'Taro']);

    $response = $this->actingAs($user)->patch('/profile', ['name' => 'Jiro']);

    $response->assertSessionHas('status');
});

test('the registration page renders a dedicated status element for the password registration fallback', function () {
    $response = $this->get(route('profile.create'));

    $response->assertOk();
    $response->assertSee('id="password-registration-status"', false);
});

test('the registration page renders the already-registered-notice and registration-forms components with the original nesting', function () {
    $response = $this->withSession(['pending_invitation_token' => 'some-token'])->get(route('profile.create'));

    $response->assertOk();

    $html = $response->getContent();
    $noticePosition = mb_strpos($html, 'id="already-registered-notice"');
    $formsPosition = mb_strpos($html, 'id="registration-forms"');
    $passkeyFormPosition = mb_strpos($html, 'id="profile-create-form"');
    $showPasswordButtonPosition = mb_strpos($html, 'id="show-password-register"');
    $passwordFormPosition = mb_strpos($html, 'id="password-register-form"');

    expect($noticePosition)->not->toBeFalse();
    expect($formsPosition)->toBeGreaterThan($noticePosition);
    expect($passkeyFormPosition)->toBeGreaterThan($formsPosition);
    expect($showPasswordButtonPosition)->toBeGreaterThan($passkeyFormPosition);
    expect($passwordFormPosition)->toBeGreaterThan($showPasswordButtonPosition);
});

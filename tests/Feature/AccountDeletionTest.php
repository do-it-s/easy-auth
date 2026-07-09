<?php

use DoITs\EasyAuth\Events\AccountDeleted;
use DoITs\EasyAuth\Events\AccountDeleting;
use DoITs\EasyAuth\Models\Invitation;
use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Notifications\AccountDeletionLinkNotification;
use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

test('a valid signed link shows the account deletion confirmation page', function () {
    $user = User::factory()->create(['name' => 'Taro']);
    $url = URL::temporarySignedRoute('account-deletion.show', now()->addMinutes(15), ['id' => $user->id]);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertSee('Taro');
    $response->assertSee('id="name"', false);
});

test('the account-deleted page renders the account-deleted-notice component with its device-clearing trigger id', function () {
    $response = $this->get(route('account-deletion.deleted'));

    $response->assertOk();
    $response->assertSee('id="account-deleted-page"', false);
});

test('a tampered signature is rejected', function () {
    $user = User::factory()->create();
    $url = URL::temporarySignedRoute('account-deletion.show', now()->addMinutes(15), ['id' => $user->id]);

    $response = $this->get($url.'tampered');

    $response->assertForbidden();
});

test('an expired signature is rejected', function () {
    $user = User::factory()->create();
    $url = URL::temporarySignedRoute('account-deletion.show', now()->subMinute(), ['id' => $user->id]);

    $response = $this->get($url);

    $response->assertForbidden();
});

test('a request with no signature at all is rejected', function () {
    $user = User::factory()->create();

    $response = $this->get('/account-deletion/'.$user->id);

    $response->assertForbidden();
});

test('confirming with the correct name deletes the account and cascades cleanly', function () {
    $user = User::factory()->create(['name' => 'Taro', 'password' => 'password']);
    $device = $user->device()->create(['uuid' => (string) Str::uuid()]);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($user, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);
    $invitation = Invitation::factory()->create(['created_by' => $user->id]);

    $url = URL::temporarySignedRoute('account-deletion.show', now()->addMinutes(15), ['id' => $user->id]);

    $response = $this->delete($url, ['name' => 'Taro']);

    $response->assertRedirect(route('account-deletion.deleted'));
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertDatabaseMissing('devices', ['id' => $device->id]);
    $this->assertDatabaseMissing('tenant_user', ['user_id' => $user->id]);
    $this->assertDatabaseHas('invitations', ['id' => $invitation->id, 'created_by' => null]);
});

test('confirming with the correct name dispatches AccountDeleting and AccountDeleted', function () {
    Event::fake([AccountDeleting::class, AccountDeleted::class]);
    $user = User::factory()->create(['name' => 'Taro']);
    $url = URL::temporarySignedRoute('account-deletion.show', now()->addMinutes(15), ['id' => $user->id]);

    $this->delete($url, ['name' => 'Taro']);

    Event::assertDispatched(AccountDeleting::class, fn ($event) => $event->user->is($user));
    Event::assertDispatched(AccountDeleted::class, fn ($event) => $event->user->is($user));
});

test('confirming with the wrong name does not dispatch AccountDeleting or AccountDeleted', function () {
    Event::fake([AccountDeleting::class, AccountDeleted::class]);
    $user = User::factory()->create(['name' => 'Taro']);
    $url = URL::temporarySignedRoute('account-deletion.show', now()->addMinutes(15), ['id' => $user->id]);

    $this->delete($url, ['name' => 'Wrong Name']);

    Event::assertNotDispatched(AccountDeleting::class);
    Event::assertNotDispatched(AccountDeleted::class);
});

test('confirming with the wrong name does not delete the account', function () {
    $user = User::factory()->create(['name' => 'Taro']);
    $url = URL::temporarySignedRoute('account-deletion.show', now()->addMinutes(15), ['id' => $user->id]);

    $response = $this->delete($url, ['name' => 'Wrong Name']);

    $response->assertRedirect();
    $response->assertSessionHasErrors('name');
    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

test('a sole tenant admin can still delete their account', function () {
    $admin = User::factory()->create(['name' => 'Admin']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);

    $url = URL::temporarySignedRoute('account-deletion.show', now()->addMinutes(15), ['id' => $admin->id]);

    $response = $this->delete($url, ['name' => 'Admin']);

    $response->assertRedirect(route('account-deletion.deleted'));
    $this->assertDatabaseMissing('users', ['id' => $admin->id]);
});

test('the full flow: a device-mismatched sign-in emails a link that deletes the account when confirmed', function () {
    Notification::fake();
    $user = User::factory()->create(['name' => 'Taro', 'email' => 'taro@example.com', 'password' => 'password']);
    $user->device()->create(['uuid' => (string) Str::uuid()]);

    $this->postJson('/login', [
        'email' => 'taro@example.com',
        'password' => 'password',
    ], ['X-Device-Uuid' => (string) Str::uuid()]);

    $url = null;
    Notification::assertSentTo($user, AccountDeletionLinkNotification::class, function ($notification) use (&$url) {
        $url = $notification->url;

        return true;
    });

    $this->get($url)->assertOk();

    $response = $this->delete($url, ['name' => 'Taro']);

    $response->assertRedirect(route('account-deletion.deleted'));
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

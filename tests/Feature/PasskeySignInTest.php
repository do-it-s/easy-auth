<?php

use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

/**
 * Builds the same shape of request laravel/passkeys' PasskeyLoginController
 * passes through Passkeys::allowsLogin() — the only extension point this
 * package has to inspect/modify a passkey sign-in before that controller
 * reads $request->remember() and logs the user in.
 */
function passkeyLoginRequest(?string $deviceUuid, ?bool $remember = null): Request
{
    $payload = $remember === null ? [] : ['remember' => $remember];

    $request = Request::create(
        '/passkeys/login',
        'POST',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        json_encode($payload)
    );

    if ($deviceUuid !== null) {
        $request->headers->set('X-Device-Uuid', $deviceUuid);
    }

    return $request;
}

function passkeyFor(User $user): Passkey
{
    $passkey = new Passkey;
    $passkey->setRelation('user', $user);

    return $passkey;
}

test('passkey sign-in is remembered past the browser session by default', function () {
    $user = User::factory()->create();
    $device = $user->device()->create(['uuid' => (string) Str::uuid()]);

    $request = passkeyLoginRequest($device->uuid);

    expect(Passkeys::allowsLogin($request, passkeyFor($user)))->toBeTrue();
    expect($request->boolean('remember'))->toBeTrue();
});

test('passkey sign-in is not remembered once the config option is disabled', function () {
    config(['easy-auth.remember_me' => false]);

    $user = User::factory()->create();
    $device = $user->device()->create(['uuid' => (string) Str::uuid()]);

    $request = passkeyLoginRequest($device->uuid);

    Passkeys::allowsLogin($request, passkeyFor($user));

    expect($request->boolean('remember'))->toBeFalse();
});

test('an explicit remember=false from the client is respected even though the config default is enabled', function () {
    $user = User::factory()->create();
    $device = $user->device()->create(['uuid' => (string) Str::uuid()]);

    $request = passkeyLoginRequest($device->uuid, remember: false);

    Passkeys::allowsLogin($request, passkeyFor($user));

    expect($request->boolean('remember'))->toBeFalse();
});

test('passkey sign-in from a device with a mismatched UUID is still rejected', function () {
    $user = User::factory()->create();
    $user->device()->create(['uuid' => (string) Str::uuid()]);

    $request = passkeyLoginRequest((string) Str::uuid());

    expect(Passkeys::allowsLogin($request, passkeyFor($user)))->toBeFalse();
});

<?php

use DoITs\EasyAuth\Http\Controllers\Auth\PwaResyncController;
use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

/**
 * A raw Request with a real, started session attached. Both
 * PwaResyncController::show() and the authorizeLoginUsing hook under test
 * here read/write the session directly, which a bare Request::create() has
 * none of.
 */
function pwaResyncRequest(?string $deviceUuid = null): Request
{
    $request = Request::create('/pwa-resync-test', 'GET');

    $session = app('session')->driver();
    $session->start();
    $request->setLaravelSession($session);

    if ($deviceUuid !== null) {
        $request->headers->set('X-Device-Uuid', $deviceUuid);
    }

    return $request;
}

function pwaResyncPasskeyFor(User $user): Passkey
{
    $passkey = new Passkey;
    $passkey->setRelation('user', $user);

    return $passkey;
}

test('visiting the resync page marks the session as having reached it', function () {
    $request = pwaResyncRequest();

    $response = (new PwaResyncController)->show($request);

    expect($response)->toBeInstanceOf(View::class);
    expect($request->session()->get('pwa_resync_allowed'))->toBeTrue();
});

test('an already-authenticated visit to the resync page redirects home without touching the session flag', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = pwaResyncRequest();

    $response = (new PwaResyncController)->show($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getTargetUrl())->toBe(route('home'));
    expect($request->session()->has('pwa_resync_allowed'))->toBeFalse();
});

test('a passkey ceremony with no X-Device-Uuid header is allowed once the session proved it reached the resync page', function () {
    $user = User::factory()->create();
    $device = $user->device()->create(['uuid' => (string) Str::uuid()]);

    $request = pwaResyncRequest(deviceUuid: null);
    $request->session()->put('pwa_resync_allowed', true);

    expect(Passkeys::allowsLogin($request, pwaResyncPasskeyFor($user)))->toBeTrue();

    // Single-use: a second attempt in the same session must not coast on it.
    expect($request->session()->has('pwa_resync_allowed'))->toBeFalse();

    // The response decorator (Http\Responses\PasskeyLoginResponse) needs
    // this to hand the UUID back to the client for re-storage.
    expect($request->session()->get('pwa_resync_device_uuid'))->toBe($device->uuid);
});

test('a passkey ceremony with no X-Device-Uuid header is rejected without the session flag', function () {
    $user = User::factory()->create();
    $user->device()->create(['uuid' => (string) Str::uuid()]);

    $request = pwaResyncRequest(deviceUuid: null);

    expect(Passkeys::allowsLogin($request, pwaResyncPasskeyFor($user)))->toBeFalse();
    expect($request->session()->has('pwa_resync_device_uuid'))->toBeFalse();
});

test('the resync flag does not relax an actually mismatched X-Device-Uuid', function () {
    $user = User::factory()->create();
    $user->device()->create(['uuid' => (string) Str::uuid()]);

    $request = pwaResyncRequest(deviceUuid: (string) Str::uuid());
    $request->session()->put('pwa_resync_allowed', true);

    expect(Passkeys::allowsLogin($request, pwaResyncPasskeyFor($user)))->toBeFalse();

    // Untouched: a mismatched (non-empty) header never reaches the
    // "missing" branch, so the flag survives for whatever request actually
    // arrives empty-headed.
    expect($request->session()->has('pwa_resync_allowed'))->toBeTrue();
});

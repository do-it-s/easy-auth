<?php

use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\TextStringObject;
use DoITs\EasyAuth\Events\UserRegistered;
use DoITs\EasyAuth\Models\Invitation;
use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Passkeys\Actions\StorePasskey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorData;

/**
 * Verifying a real WebAuthn attestation (COSE key parsing, signature
 * checks, ...) is laravel/passkeys' own responsibility to test, not this
 * package's — PasskeySignInTest bypasses the equivalent assertion ceremony
 * the same way, by calling Passkeys::allowsLogin() directly. Here
 * StorePasskey itself is faked so this test can exercise everything
 * ProfileController::store() is actually responsible for: user/device
 * creation, the UserRegistered('passkey') dispatch, and the response shape.
 *
 * The credential payload below is still real wire-format WebAuthn JSON (a
 * genuine CBOR-encoded "none" attestation object, base64url id/rawId that
 * match per PublicKeyCredentialDenormalizer's hash_equals check, ...) so it
 * survives PasskeyRegistrationRequest's real (non-cryptographic) JSON
 * deserialization and RejectSyncedPasskey's flag check — it just has no
 * attested credential data or signature, since nothing here validates those.
 */
function registrationCredentialPayload(): array
{
    $rawId = random_bytes(16);

    $authData = str_repeat("\0", 32).chr(AuthenticatorData::FLAG_UP).str_repeat("\0", 4);

    $attestationObjectCbor = (string) MapObject::create()
        ->add(TextStringObject::create('fmt'), TextStringObject::create('none'))
        ->add(TextStringObject::create('attStmt'), MapObject::create())
        ->add(TextStringObject::create('authData'), ByteStringObject::create($authData));

    $json = json_encode([
        'id' => Base64UrlSafe::encodeUnpadded($rawId),
        'rawId' => base64_encode($rawId),
        'type' => 'public-key',
        'response' => [
            'clientDataJSON' => Base64UrlSafe::encodeUnpadded(json_encode([
                'type' => 'webauthn.create',
                'challenge' => Base64UrlSafe::encodeUnpadded('challenge'),
                'origin' => 'https://example.test',
            ])),
            'attestationObject' => base64_encode($attestationObjectCbor),
            'transports' => [],
        ],
    ]);

    return json_decode($json, true);
}

function fakeStorePasskey(): void
{
    test()->mock(StorePasskey::class, function ($mock) {
        $mock->shouldReceive('__invoke')->andReturnUsing(
            fn ($user, $name) => $user->passkeys()->create([
                'name' => $name,
                'credential_id' => Str::random(32),
                'credential' => ['type' => 'public-key'],
            ])
        );
    });
}

beforeEach(function () {
    config([
        'passkeys.relying_party_id' => 'example.test',
        'passkeys.user_handle_secret' => 'test-secret',
    ]);
});

test('a user can register with a passkey and is logged in', function () {
    fakeStorePasskey();

    $this->getJson('/profile/passkey-options?name=Taro');

    $response = $this->postJson('/profile', [
        'name' => 'Taro',
        'credential' => registrationCredentialPayload(),
    ]);

    $response->assertOk()->assertJson(['status' => 'passkey-registered']);

    // The passkey's own name is 'Taro' (the form field of that name), but
    // ProfileController::store() creates the User row itself with an empty
    // name — filling it in is deferred to the "complete your profile" step.
    $user = User::has('passkeys')->sole();

    expect($user)->not->toBeNull();
    $this->assertAuthenticatedAs($user);

    expect($user->device)->not->toBeNull();
    $response->assertJson(['device_uuid' => $user->device->uuid]);

    expect($user->passkeys)->toHaveCount(1);
});

test('registering with a passkey redeems a pending invitation', function () {
    fakeStorePasskey();

    $tenant = Tenant::factory()->create();
    $token = Invitation::generateToken();
    $invitation = Invitation::factory()->for($tenant)->create([
        'token' => Invitation::hashToken($token),
    ]);

    $this->getJson('/profile/passkey-options?name=Taro');

    $this->withSession(['pending_invitation_token' => $token])->postJson('/profile', [
        'name' => 'Taro',
        'credential' => registrationCredentialPayload(),
    ])->assertOk();

    $user = User::has('passkeys')->sole();

    expect($user->tenants)->toHaveCount(1);
    expect($user->tenants->first()->id)->toBe($tenant->id);
    expect($invitation->refresh()->isUsed())->toBeTrue();
});

test('registering with a passkey dispatches UserRegistered with the passkey auth method', function () {
    fakeStorePasskey();
    Event::fake([UserRegistered::class]);

    $this->getJson('/profile/passkey-options?name=Taro');

    $this->postJson('/profile', [
        'name' => 'Taro',
        'credential' => registrationCredentialPayload(),
    ]);

    $user = User::has('passkeys')->sole();

    Event::assertDispatched(UserRegistered::class, fn ($event) => $event->user->is($user) && $event->authMethod === 'passkey');
});

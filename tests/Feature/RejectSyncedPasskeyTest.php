<?php

use DoITs\EasyAuth\Actions\RejectSyncedPasskey;
use Illuminate\Validation\ValidationException;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AttestationStatement\AttestationObject;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorData;
use Webauthn\CollectedClientData;
use Webauthn\PublicKeyCredential;
use Webauthn\TrustPath\EmptyTrustPath;

function registrationCredentialWithFlags(int $flags): PublicKeyCredential
{
    $clientData = CollectedClientData::create('', [
        'type' => 'webauthn.create',
        'challenge' => Base64UrlSafe::encodeUnpadded('challenge'),
        'origin' => 'https://example.test',
    ]);

    $authData = AuthenticatorData::create(
        authData: '',
        rpIdHash: '',
        flags: chr($flags),
        signCount: 0,
    );

    $attestationObject = AttestationObject::create(
        '',
        AttestationStatement::createNone('none', [], EmptyTrustPath::create()),
        $authData,
    );

    return PublicKeyCredential::create(
        'public-key',
        'raw-id',
        AuthenticatorAttestationResponse::create($clientData, $attestationObject),
    );
}

test('allows a backup-eligible passkey by default', function () {
    $credential = registrationCredentialWithFlags(AuthenticatorData::FLAG_UP | AuthenticatorData::FLAG_BE);

    app(RejectSyncedPasskey::class)($credential);
})->throwsNoExceptions();

test('rejects a passkey the authenticator reports as backup eligible once enabled', function () {
    config(['easy-auth.reject_backup_eligible_passkeys' => true]);

    $credential = registrationCredentialWithFlags(AuthenticatorData::FLAG_UP | AuthenticatorData::FLAG_BE);

    expect(fn () => app(RejectSyncedPasskey::class)($credential))
        ->toThrow(ValidationException::class);
});

test('allows a passkey the authenticator reports as device-bound once enabled', function () {
    config(['easy-auth.reject_backup_eligible_passkeys' => true]);

    $credential = registrationCredentialWithFlags(AuthenticatorData::FLAG_UP);

    app(RejectSyncedPasskey::class)($credential);
})->throwsNoExceptions();

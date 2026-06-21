<?php

namespace DoITs\EasyAuth\Actions;

use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredential;

class RejectSyncedPasskey
{
    /**
     * Reject passkeys the authenticator reports as backup eligible
     * (WebAuthn's BE flag) — i.e. synced across devices via a platform
     * credential manager such as iCloud Keychain or Google Password
     * Manager. This package pairs passkeys with a device UUID to assert
     * "this specific device", a guarantee a synced passkey silently
     * defeats by working from any device it propagated to. This is a
     * fixed policy for now; making it configurable per host app is a
     * known future enhancement.
     *
     * @throws InvalidPasskeyException
     */
    public function __invoke(PublicKeyCredential $credential): void
    {
        $response = $credential->response;

        if ($response instanceof AuthenticatorAttestationResponse
            && $response->attestationObject->authData->isBackupEligible()) {
            throw InvalidPasskeyException::make('easy-auth::profile.passkey_backup_eligible_rejected');
        }
    }
}

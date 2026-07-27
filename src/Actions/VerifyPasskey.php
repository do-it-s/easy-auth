<?php

namespace DoITs\EasyAuth\Actions;

use DoITs\EasyAuth\Support\AuditLog;
use Laravel\Passkeys\Actions\VerifyPasskey as BaseVerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

class VerifyPasskey extends BaseVerifyPasskey
{
    /**
     * Records a passkey.verification_failed audit entry before rethrowing.
     *
     * InvalidPasskeyException extends ValidationException, which is in
     * Laravel's Handler::$internalDontReport list, so a reportable()
     * callback registered for it in EasyAuthServiceProvider would never
     * run. This override is the only reachable hook for a failed WebAuthn
     * assertion, since the vendor action dispatches an event on success
     * (PasskeyVerified) but not on failure.
     */
    public function __invoke(PublicKeyCredential $credential, PublicKeyCredentialRequestOptions $options, ?PasskeyUser $user = null): Passkey
    {
        try {
            return parent::__invoke($credential, $options, $user);
        } catch (InvalidPasskeyException $e) {
            AuditLog::record('passkey.verification_failed', 'failure', [
                'reason' => $e->errors()['credential'][0] ?? null,
            ]);

            throw $e;
        }
    }
}

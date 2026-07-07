<?php

namespace DoITs\EasyAuth\Actions;

use Laravel\Passkeys\Actions\GenerateRegistrationOptions as BaseGenerateRegistrationOptions;
use Webauthn\AuthenticatorSelectionCriteria;

class GenerateRegistrationOptions extends BaseGenerateRegistrationOptions
{
    /**
     * Force registration onto the platform authenticator (Windows Hello,
     * Touch ID, etc.), excluding cross-platform authenticators and
     * password-manager-only options from the browser's picker. Gated
     * behind config('easy-auth.force_platform_authenticator'), defaulting
     * to off: an environment where the platform authenticator is broken
     * or unreachable (e.g. hidden behind a password-manager extension)
     * would otherwise be left with zero eligible authenticators and unable
     * to register at all. See README's known-limitations section.
     */
    public function authenticatorSelection(): AuthenticatorSelectionCriteria
    {
        $criteria = parent::authenticatorSelection();

        if (! config('easy-auth.force_platform_authenticator')) {
            return $criteria;
        }

        return AuthenticatorSelectionCriteria::create(
            authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
            userVerification: $criteria->userVerification,
            residentKey: $criteria->residentKey,
        );
    }
}

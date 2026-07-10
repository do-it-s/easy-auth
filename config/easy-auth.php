<?php

return [

    // Reject passkeys the authenticator reports as backup-eligible (WebAuthn's
    // BE flag), i.e. synced across devices via iCloud Keychain, Google
    // Password Manager, etc. Defaults to false: a hard rejection with no
    // device-bound alternative reachable (e.g. a password-manager browser
    // extension hiding the platform authenticator from the OS's passkey
    // picker) can lock a legitimate user out of registration entirely, which
    // this package's no-fallback design treats as worse than the device
    // UUID's organization-boundary guarantee being weakened by a synced
    // passkey. See README's known-limitations section.
    'reject_backup_eligible_passkeys' => env('EASY_AUTH_REJECT_BACKUP_ELIGIBLE_PASSKEYS', false),

    // Force passkey registration onto the platform authenticator (Windows
    // Hello, Touch ID, etc.), excluding cross-platform authenticators and
    // password-manager-only options from the browser's picker. Defaults to
    // false: an environment where the platform authenticator is broken or
    // hidden behind a password-manager extension would otherwise be left
    // with zero eligible authenticators and unable to register at all. See
    // README's known-limitations section.
    'force_platform_authenticator' => env('EASY_AUTH_FORCE_PLATFORM_AUTHENTICATOR', false),

    // Whether a successful sign-in (or initial registration) extends the
    // session past the browser closing, via Laravel's "remember me" cookie.
    // Defaults to true: this package's whole premise is a device-bound
    // session standing in for repeated authentication, so forcing a fresh
    // passkey/password ceremony every time the browser restarts would work
    // against that. Applies to email+password sign-in (SignInController)
    // and to passkey sign-in (via the "remember" field laravel/passkeys
    // reads from the request, defaulted here through the existing
    // Passkeys::authorizeLoginUsing hook in EasyAuthServiceProvider since
    // this package's own JS client never sends one) and to both
    // registration flows' immediate login.
    'remember_me' => env('EASY_AUTH_REMEMBER_ME', true),

    // Whether invitation creators may set a maximum redemption count greater
    // than one, letting a single invitation link be reused by several
    // people. Defaults to false: this is a system-administration knob (it
    // trades the device-UUID/invitation-chain model's implicit one-invite-
    // per-person audit trail for convenience), not something an ordinary
    // tenant admin or member should be able to reach for on their own. When
    // disabled, the "maximum uses" field is hidden from the invitation form
    // and every invitation is created single-use (max_uses forced to 1
    // server-side, regardless of what the request submits).
    'multi_use_invitations' => env('EASY_AUTH_MULTI_USE_INVITATIONS', false),

];

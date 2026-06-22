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

];

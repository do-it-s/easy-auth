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

];

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

    // Whether invitation creators may set a custom expiry (or no expiry at
    // all) instead of the fixed default (Invitation::DEFAULT_EXPIRATION_MINUTES,
    // currently 30 minutes). Defaults to false: this package's whole premise
    // is a face-to-face invitation chain that spreads through an organization
    // in a short window, and a long-lived or non-expiring invitation link
    // works against that. When disabled, the "expires at" field is hidden
    // from the invitation form and every invitation is forced to expire
    // DEFAULT_EXPIRATION_MINUTES from creation (expires_at forced server-side,
    // regardless of what the request submits).
    'custom_invitation_expiration' => env('EASY_AUTH_CUSTOM_INVITATION_EXPIRATION', false),

    // Audit log of authentication attempts and tenant/invitation/profile
    // operations, written via a dedicated daily-rotating log channel
    // (EasyAuthServiceProvider registers it automatically unless the host
    // app already defines a channel of this name in its own logging.php).
    // Read directly with tail/grep; there is no in-app viewer. Entries never
    // include credentials or attempted email addresses — only IDs, names,
    // and outcomes — since this is disk-persisted independently of the
    // database rows it describes.
    'audit_log_channel' => env('EASY_AUTH_AUDIT_LOG_CHANNEL', 'easy-auth-audit'),

    // Days of daily audit log files to retain before Laravel's daily driver
    // prunes them.
    'audit_log_retention_days' => env('EASY_AUTH_AUDIT_LOG_RETENTION_DAYS', 30),

    // Page sizes for the tenant member list's admin/member sections and the
    // invitation list. Each defaults to null, which is treated as "no limit"
    // (PHP_INT_MAX is passed to paginate()) so an app that never sets these
    // keeps seeing today's single-page, un-paginated list. Set an integer to
    // turn pagination on for that list. The member list's admin and member
    // sections are paginated independently (an org with many admins doesn't
    // imply many members, or vice versa), so they get separate knobs.
    'members_admins_per_page' => env('EASY_AUTH_MEMBERS_ADMINS_PER_PAGE'),
    'members_others_per_page' => env('EASY_AUTH_MEMBERS_OTHERS_PER_PAGE'),
    'invitations_per_page' => env('EASY_AUTH_INVITATIONS_PER_PAGE'),

    // The single user ID treated as this package's system administrator,
    // consulted by isSysAdmin() (Contracts\EasyAuthUser). Deliberately not
    // matched by email: most easy-auth accounts are passkey-only and never
    // set one, so an email-based check would miss the majority of accounts.
    // Also deliberately a single ID rather than a list: there is no current
    // need for more than one system administrator at a time. Defaults to
    // null (no system administrator). Set via .env, since the ID is
    // specific to each service's own database, not something this config
    // file's defaults could sensibly guess.
    'sysadmin_user_id' => env('EASY_AUTH_SYSADMIN_USER_ID'),

    // An unguessable path (e.g. a long random string) this package will
    // register a GET route on to re-establish a signed-in-elsewhere user's
    // device_uuid in a browsing context whose localStorage never received
    // it — chiefly a home-screen PWA on iOS/iPadOS, whose storage is
    // isolated from Safari's even though its manifest.json's start_url can
    // point anywhere on the same origin. Visiting this path attempts a
    // passkey ceremony and silently redirects to the app regardless of
    // outcome; success re-stores the device's existing UUID into
    // localStorage without a fresh registration. Defaults to null (feature
    // off, no route registered). Deliberately has no package-side default
    // value: a path shipped in this package's public source would not be
    // unguessable for any app using the default, defeating the point.
    // Each app must set its own random value via .env. See README's PWA
    // section.
    'pwa_resync_path' => env('EASY_AUTH_PWA_RESYNC_PATH'),

];

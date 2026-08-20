# Changelog

All notable changes to this package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.9] - 2026-08-21

### Fixed

- `resources/js/dist/easy-auth.js` (the bundled JS shipped to consumers via
  `composer require`) was committed to `v0.2.8` without being rebuilt after
  `index.js` gained `attemptPwaResync()`, so the PWA re-sync feature added
  in that release shipped without its JS half and never fired. Rebuilt the
  bundle to include it.
- A Pint style violation (`single_line_empty_body`) in
  `PasskeyLoginResponse.php` that had been slipping the local pre-commit
  workflow and failing CI.

## [0.2.8] - 2026-08-20

### Added

- A PWA re-sync route for home-screen-installed apps whose isolated
  `localStorage` never received the device_uuid Safari holds, via
  `config('easy-auth.pwa_resync_path')` (`.env`'s `EASY_AUTH_PWA_RESYNC_PATH`,
  default `null`, no package-provided default value). Point a PWA
  manifest's `start_url` at this app-chosen unguessable path to have it
  silently re-establish the device's existing device_uuid via a passkey
  ceremony on first open. See README's new "PWA (Home Screen) Sign-In"
  section.

## [0.2.7] - 2026-08-10

### Fixed

- The tenant member list's two independent paginators (admin and
  non-admin sections) each reset the other to page 1 when paged, since
  their generated links didn't carry the other section's `_page` query
  parameter forward. Added upon `v0.2.6`'s pagination feature. Both
  paginators now call `withQueryString()`.

## [0.2.6] - 2026-08-10

### Added

- Pagination for the tenant member list (admin and non-admin sections,
  independently) and the invitation list, via `config('easy-auth.members_admins_per_page')`,
  `config('easy-auth.members_others_per_page')`, and `config('easy-auth.invitations_per_page')`
  (`.env`'s `EASY_AUTH_MEMBERS_ADMINS_PER_PAGE`, `EASY_AUTH_MEMBERS_OTHERS_PER_PAGE`,
  `EASY_AUTH_INVITATIONS_PER_PAGE`), each defaulting to `null` (no limit,
  matching prior versions' single-page behavior). Bundled views render
  Laravel's default `links()` markup when a limit is configured. See
  README's new "Pagination" section.

## [0.2.5] - 2026-08-03

### Added

- A system-administrator identification primitive: `config('easy-auth.sysadmin_user_id')`
  (`.env`'s `EASY_AUTH_SYSADMIN_USER_ID`, a single user ID, default `null`)
  and `Contracts\EasyAuthUser::isSysAdmin(): bool` (implemented in
  `Concerns\IsEasyAuthUser`). Identifies by ID rather than email, since
  most easy-auth accounts are passkey-only and never set one. This is
  phase 1 of a larger system-administrator feature — it does not yet
  grant any special tenant access on its own; host apps can already use
  `isSysAdmin()` to gate their own app-specific administrative features.
  See README's new "System Administrator" section.

## [0.2.4] - 2026-07-27

### Added

- Audit log of authentication attempts and tenant/invitation/profile
  operations, written to a dedicated daily-rotating log channel
  (`config('easy-auth.audit_log_channel')`, default `easy-auth-audit`;
  retention via `config('easy-auth.audit_log_retention_days')`, default 30
  days). Covers password and passkey sign-in (success, bad credentials, and
  device mismatch), sign-out, passkey registration/verification/deletion,
  and every operation already covered by this package's `*ed` events
  (tenant/invitation/member/profile/account changes). Never logs
  credentials or an attempted-but-wrong email address. See README's new
  "Audit Log" section.

## [0.2.3] - 2026-07-14

### Fixed

- The member list's "Remove" button (`tenants.members.destroy`) submitted
  immediately without showing the `confirm()` dialog. The confirmation
  message was rendered with `@json()` inside a double-quoted `onsubmit`
  attribute, and the JSON string's own double quotes terminated the HTML
  attribute early, leaving `onsubmit="return confirm("` — invalid
  JavaScript that browsers silently discard. Switched to `{{ json_encode(...) }}`,
  which HTML-entity-escapes both `"` and `'` and is safe regardless of the
  message content or the attribute's quote style.

## [0.2.2] - 2026-07-13

### Added

- `config('easy-auth.custom_invitation_expiration')` (`.env`'s
  `EASY_AUTH_CUSTOM_INVITATION_EXPIRATION`), mirroring
  `multi_use_invitations`. Defaults to false: the invitation form's
  "expires at" field is hidden and every invitation is forced to expire
  `Invitation::DEFAULT_EXPIRATION_MINUTES` (30 minutes) from creation,
  regardless of any submitted value. Enable it to let invitation creators
  set a custom expiry or leave it blank for no expiry at all.

## [0.2.1] - 2026-07-11

### Fixed

- The invitation list's "Revoke" button no longer appears for invitations
  that have already been fully used (including exhausted multi-use
  invitations), which previously offered a revoke action that had no
  meaningful effect.

## [0.2.0] - 2026-07-10

### Changed

- `@do-it-s/easy-auth-js` (the passkey/sign-in JS in `resources/js/`) now
  ships pre-bundled as `resources/js/dist/easy-auth.js` (a single ESM file
  with no unresolved imports) instead of requiring a separate npm install
  of `@laravel/passkeys`. Host apps still consume it via a `file:`
  dependency, now pointing at `vendor/do-it-s/easy-auth/resources/js`
  (the copy `composer require` already placed) instead of a sibling
  `../easy-auth` checkout.

## [0.1.0] - 2026-07-10

First tagged version. Still beta (see the README's Beta Notice) — 0.x
signals the public API may still change. Passwordless authentication
(passkeys + device UUID binding) with an email/password fallback, and an
invitation-chain tenant/role model, on top of `laravel/passkeys`.

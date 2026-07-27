# Changelog

All notable changes to this package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

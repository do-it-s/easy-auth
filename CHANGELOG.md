# Changelog

All notable changes to this package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

# easy-auth

English | [日本語](README.ja.md)

A Laravel package providing passwordless authentication via passkeys (WebAuthn) bound to device UUIDs, plus an invitation-chain tenant/role model. It wraps `laravel/passkeys` and adds an email+password fallback for devices without passkey support, along with per-tenant invitations and backup codes.

## ⚠️ Beta Notice

This package is registered on Packagist but is still in beta (0.x, pre-1.0). The author is a web application developer, not an authentication or security specialist, and some design decisions still have open questions (see "Known Limitations and Future Considerations" below). The goal is to reach 1.0 once these and other known issues are addressed. Feedback from anyone with authentication or security expertise is very welcome.

## Requirements

- PHP ^8.3, Laravel ^13.0
- `laravel/passkeys` ^0.2.1, `endroid/qr-code` 6.0.* (pulled in automatically by Composer)
- The host app's standard Laravel migrations (`users`, `password_reset_tokens`, etc.) must not have been removed. This package does not own the `users` table itself, and its password-reset feature relies directly on the host app's standard `password_reset_tokens` table.

## Installation

### 1. Add the dependency via Composer

```
composer require do-it-s/easy-auth
```

To pick up updates once easy-auth itself has been updated, run:

```
composer update do-it-s/easy-auth
```

If this fails with a dependency conflict mentioning `brick/math`, it's not a problem with easy-auth itself: a fresh `laravel/laravel` project locks `brick/math` to `0.18.x`, but `spomky-labs/cbor-php` (pulled in transitively via `laravel/passkeys` → `web-auth/webauthn-lib`) only allows `brick/math ^0.17` as of its latest tagged release, so Composer's default partial update refuses to touch it. Retrying with `-W` (`--with-all-dependencies`) resolves this safely by letting Composer downgrade `brick/math` to `0.17.x` — `laravel/framework` itself already allows that range, so nothing else breaks:

```
composer require do-it-s/easy-auth -W
```

This gap is expected to close once cbor-php's upstream support for `brick/math ^0.18` (already in progress as of this writing) is tagged and released.

### 2. Publish `laravel/passkeys` migrations

`laravel/passkeys` does not auto-load the migration for the `passkeys` table (since the type of `users.id` depends on the host app, it uses a publish-based approach so the app can edit it).

```
php artisan vendor:publish --tag=passkeys-migrations --no-interaction
```

### 3. Wire up the User model

```php
use DoITs\EasyAuth\Concerns\IsEasyAuthUser;
use DoITs\EasyAuth\Contracts\EasyAuthUser;

class User extends Authenticatable implements EasyAuthUser
{
    use HasFactory, IsEasyAuthUser, Notifiable;
}
```

`EasyAuthUser` extends `laravel/passkeys`'s `PasskeyUser`, and `IsEasyAuthUser` uses `PasskeyAuthenticatable`, so the app only needs this one interface plus one trait (no need to write `PasskeyUser`/`PasskeyAuthenticatable` separately).

### 4. Provide `resources/views/layouts/app.blade.php`

Every view in this package does `@extends('layouts.app')`. At minimum, the app's layout must satisfy:

| Element | Reason |
|---|---|
| `@yield('content')` | Where each view's `@section('content')` is injected |
| `@stack('scripts')` | `device/reset.blade.php` and others `@push('scripts')` here |
| `<meta name="csrf-token" content="{{ csrf_token() }}">` | Used by the JS side's fetch calls for `X-CSRF-TOKEN` |

Header navigation, branding, and the choice of UI framework (Alpine.js, etc.) are otherwise entirely up to the app. If you build a tenant-switcher UI in the header using helpers like `$user->currentTenant()`, `$user->tenants`, `$tenant->isAdministeredBy()`, `$tenant->hasUsableBackupCode()`, reference this package's model namespace, e.g. `@can('create', [\DoITs\EasyAuth\Models\Invitation::class, $tenant])`.

### 5. Provide a `home` route

After sign-in, registration, invitation acceptance, tenant switching, password change, etc. complete, this package redirects via `redirect()->route('home')`. This package does not define a route named `home` itself, so the app must provide one.

This home screen must correctly handle the case where the signed-in user does not yet belong to any tenant (`$user->currentTenant()` returns `null`). This "no tenant" state is a normal, expected condition — for example, right after registering with no invitation yet accepted, or right after leaving the only tenant the user belonged to. This package is designed to allow such users to sign in, create a tenant, or accept an invitation without issue, and currently has no mechanism to automatically delete or clean up tenant-less users (a possible future consideration). The home screen should branch on the "no tenant" state as an expected case.

### 6. Run migrations

```
php artisan migrate
```

### 7. Frontend (JS)

The passkey registration/sign-in/password-fallback JS in `resources/js/` is packaged as `@do-it-s/easy-auth-js` and ships pre-bundled (`resources/js/dist/easy-auth.js` — a single ESM file with no unresolved imports, so no separate `npm install` step is needed on the package side). Add it to the app's `package.json`, pointing at the copy that `composer require` already placed in `vendor/`:

```json
{
    "dependencies": {
        "@do-it-s/easy-auth-js": "file:vendor/do-it-s/easy-auth/resources/js"
    }
}
```

In the app's `resources/js/app.js`:

```js
import { initEasyAuth } from '@do-it-s/easy-auth-js';

initEasyAuth();
```

(The app's own JS initialization — Alpine.js, etc. — can happen freely before or after this. `easy-auth-js` has no dependency on Alpine.)

If a `file:` dependency causes a symlink permission error on Windows, add `install-links=true` to `.npmrc` to switch to a copy-based install instead.

#### Sign-in (`canAttemptSignIn` / `attemptSignIn`)

On the principle that a WebAuthn ceremony should only be attempted in response to an explicit user action, this package does not attempt sign-in automatically on page load. Instead, it exposes two functions so the host app's own guest home page (e.g. the top page) can offer a "Sign in" UI. Deciding whether to show/hide that UI, and where to navigate after the attempt resolves, are entirely the app's responsibility.

```js
import { canAttemptSignIn, attemptSignIn } from '@do-it-s/easy-auth-js';

if (canAttemptSignIn()) {
    // Show UI that requires an explicit action, such as a "Sign in" button.
}

signInButton.addEventListener('click', async () => {
    const { outcome, redirect } = await attemptSignIn();

    if (outcome === 'success') {
        window.location.href = redirect;
    } else if (outcome === 'fallback') {
        // This device is a password-auth user. Send them to the package's /login.
        window.location.href = '/login';
    } else {
        // outcome === 'failure'. Could be cancellation, no matching passkey, or a
        // server rejection — the app cannot and does not need to distinguish further.
    }
});
```

- `canAttemptSignIn()` only checks whether a `device_uuid` is present — it's a **heuristic**, not a guarantee that sign-in will succeed (WebAuthn's spec offers no way to reliably know "is there a matching credential" without actually running the ceremony).
- Only call `attemptSignIn()` in response to an explicit action such as a click. It never touches the DOM or navigates, so the caller must handle display/navigation based on `outcome`.
- Earlier versions attempted sign-in automatically on reaching the top page; this version removes that automatic attempt. Without an explicit UI like the one above, even a device with an existing passkey will no longer be able to sign in (a breaking change).

#### Registration and password sign-in (primitives / `initEasyAuth`)

`registerPasskey` / `registerWithPassword` / `signInWithPassword` are primitives that never touch the DOM; they return either `{ outcome: 'success', redirect }` or `{ outcome: 'failure', code, errors? }`. `code` is one of `name_required` / `ceremony_failed` / `validation` / `server_error` / `network_error` (only `validation` includes `errors`, with per-field messages already translated on the Laravel side). If you want to build a fully custom form/UI on the app side, call these directly.

```js
import { registerPasskey, registerWithPassword, signInWithPassword } from '@do-it-s/easy-auth-js';

const result = await registerPasskey({ name, passkeyLabel: 'My device' });

if (result.outcome === 'success') {
    window.location.href = result.redirect;
} else {
    // Render your own UI based on result.code
}
```

`initEasyAuth()` is a convenience function that wires up only the known form IDs found in this package's own default views (`profile/create.blade.php`, `auth/sign-in.blade.php`, etc.). It bundles the primitives above together with default-view-specific UI conveniences, such as automatically switching to the password form when passkeys aren't supported. Apps using their own custom views simply don't call this function, and this wiring/UI convenience is naturally disabled.

```js
import { initEasyAuth } from '@do-it-s/easy-auth-js';

initEasyAuth();
```

By default, on failure it writes to the status elements (`#passkey-status`, `#sign-in-status`) present in this package's own views via `textContent`. To swap in your own UI (e.g. toast notifications), pass `onStatus` (the form wiring and WebAuthn ceremony calls themselves stay handled by `initEasyAuth()` — only the display is replaced):

```js
initEasyAuth({
    onStatus: ({ outcome, code, message }) => {
        showToast(message, outcome === 'success' ? 'alert-success' : 'alert-error');
    },
});
```

`message` is this package's pre-translated copy (for server validation, the joined `errors`). `code` is the breakdown for `outcome: 'failure'`, usable for finer-grained branching if needed.

#### Device credentials (`getDeviceCredentials` / `clearDeviceCredentials`)

Use these instead of reading/writing the two `device_uuid`/`auth_method` localStorage keys directly.

```js
import { getDeviceCredentials, clearDeviceCredentials } from '@do-it-s/easy-auth-js';

const { device_uuid, auth_method } = getDeviceCredentials();
```

### 8. Confirm the exception handler can return JSON

This package's `/login`, `/profile-password`, and similar routes are not under `api/*` — they return JSON via normal content negotiation triggered by the `Accept: application/json` header (the default behavior of `Request::expectsJson()`).

If `bootstrap/app.php` contains something like the following, routes outside `api/*` are always forced to an HTML response, and things like `ValidationException` come back as a 302 redirect (HTML) instead. The JS side then fails trying to `response.json()` that `<!DOCTYPE ...` with `Unexpected token '<' ... is not valid JSON`:

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request) => $request->is('api/*'),
    );
})
```

This sometimes ships in Laravel's default project skeleton, but for an app without a `routes/api.php`, it's effectively a constraint that prevents any route from ever being JSON-ified. When adopting easy-auth, either remove this callback (revert to an empty `withExceptions`) or rewrite it as `$request->is('api/*') || $request->expectsJson()`.

## Customizing views

Views provided by easy-auth guarantee the entire auth flow works out of the box with zero app-side setup, while still being customizable via four mechanisms:

### 1. Whole-page or per-component overrides (`vendor:publish`)

```
php artisan vendor:publish --tag=easy-auth-views
```

This copies the full set of views into `resources/views/vendor/easy-auth/`; from then on, the copied files take priority (standard Laravel view-namespace resolution). `resources/views/` is structured in two layers: "one screen = one page" (e.g. `auth/sign-in.blade.php`), and the "feature components" that hold the actual forms etc. (under `resources/views/components/`, e.g. `components/auth/sign-in-form.blade.php`). If you only want to change a page's look, you can copy just the page and keep using the original component via `<x-easy-auth::auth.sign-in-form />`, or you can replace the component entirely.

The success-status message (the green `session('status')` text) shown by the sign-in, password-reset-link, profile-edit, and organization-edit forms is factored out of each form into a small shared component, `components/shared/status-message.blade.php`. If the app wants to remove just this message (e.g. to consolidate on toast notifications), there's no need to copy and edit the whole form — simply create `resources/views/vendor/easy-auth/components/shared/status-message.blade.php` as an empty file.

### 2. Adding fields to existing forms (`@stack`)

Major form components have an injection point right before the submit button, `@stack('easy-auth::components.{domain}.{name}.after-fields')` (e.g. for the `tenants/edit` form, it's `easy-auth::components.tenants.edit-form.after-fields`). The app can add fields without copying the page, just by `@push`ing from any view.

```blade
@push('easy-auth::components.tenants.edit-form.after-fields')
    <label class="flex items-center gap-2 mb-4 text-sm">
        <input type="checkbox" name="my_app_flag" value="1">
        My custom setting
    </label>
@endpush
```

List views (`tenants/members/index`, `tenants/invitations/index`) instead expose a loop-friendly injection point per row, via `@includeIf('vendor.easy-auth.tenants.member-row-actions', [...])`. By default nothing is rendered; once the app creates `resources/views/vendor/easy-auth/tenants/member-row-actions.blade.php` (the variables passed differ per component — check each component's source), it can add its own buttons etc. to each row.

### 3. Before/after hooks for mutating operations (Laravel events)

Major mutating operations — tenant updates, invitation creation, member removal, etc. — fire events under `DoITs\EasyAuth\Events\` before and after (e.g. `TenantUpdating`/`TenantUpdated`, `InvitationCreating`/`InvitationCreated`). The `*ing` events carry the raw `Request` (or source data) rather than a validated array, so a listener can read its own fields and save them directly, even for columns not included in `fillable`.

```php
Event::listen(TenantUpdating::class, function (TenantUpdating $event): void {
    $event->tenant->my_app_column = $event->request->boolean('my_app_column');
    $event->tenant->save();
});
```

This package fires the following 25 events. The `{Model}{Verb}ing`/`{Model}{Verb}ed` naming mirrors Eloquent's `Creating`/`Created` pattern and covers every mutating operation (in-house use of listeners is currently limited, but the full set was laid out proactively so the app can hook into any operation in the future).

| Trigger | Fired from | `*ing` (before validation) | `*ed` (after save) |
| --- | --- | --- | --- |
| Registration (password) | `Auth\RegisterController` | - | `UserRegistered` (`context: 'password'`) |
| Registration (passkey) | `ProfileController` | - | `UserRegistered` (`context: 'passkey'`) |
| Profile edit | `ProfileController` | `ProfileUpdating` | `ProfileUpdated` |
| Account deletion (self, while signed in) | `ProfileController` | `AccountDeleting` | `AccountDeleted` |
| Account deletion (self-service, from device mismatch) | `Auth\AccountDeletionController` | `AccountDeleting` | `AccountDeleted` |
| Password reset | `Auth\PasswordResetController` | `PasswordResetting` | `PasswordResetCompleted` |
| Backup code issuance | `BackupCodeController` | `BackupCodeIssuing` | `BackupCodeIssued` |
| Invitation creation | `InvitationController` | `InvitationCreating` | `InvitationCreated` |
| Invitation revocation | `InvitationController` | `InvitationRevoking` | `InvitationRevoked` |
| Invitation redemption (join) | `InvitationRedemptionController` | `InvitationRedeeming` | `InvitationRedeemed` |
| Organization creation | `TenantController` | `TenantCreating` | `TenantCreated` |
| Organization edit | `TenantController` | `TenantUpdating` | `TenantUpdated` |
| Organization deletion | `TenantController` | `TenantDeleting` | `TenantDeleted` |
| Member self-removal | `TenantLeaveController` | `TenantMemberRemoving` | `TenantMemberRemoved` |
| Member removal (by admin) | `TenantMemberController` | `TenantMemberRemoving` | `TenantMemberRemoved` |
| Member role change | `TenantMemberController` | `TenantMemberRoleUpdating` | `TenantMemberRoleUpdated` |

### 4. Recomposing feature components

Components under `resources/views/components/` are self-contained and not tied to how the package's default pages combine them. For example, `profile/create` provides passkey registration (`passkey-registration-form`) and the password-registration fallback (`password-registration-form`) as independent components, so the app is free to place both on the same screen, split them into separate routes, or use only one.

## Customizing routes

The four mechanisms above replace the *contents* of existing routes; the routes themselves (URL, HTTP method, whether to use them at all) are out of scope. If the app wants to change a URL, or drop a specific feature's route entirely (e.g. the password-registration fallback), call `EasyAuth::ignoreRoutes()` from `AppServiceProvider::register()`.

```php
use DoITs\EasyAuth\EasyAuth;

public function register(): void
{
    EasyAuth::ignoreRoutes();
}
```

Once called, this package registers no `routes/web.php` at all. From then on, the app writes its own routes in its own `routes/web.php`, pointing at this package's controllers (`DoITs\EasyAuth\Http\Controllers\...`) with whatever URLs and middleware it wants (copying this package's own `routes/web.php` as a starting point is the fastest way). Simply omit routes for any feature you don't want.

## Customizing translated copy

All of this package's view and email copy goes through translation keys under the `easy-auth::` namespace (`lang/en`, `lang/ja`). Thanks to Laravel's standard mechanism, placing a same-named file under `lang/vendor/easy-auth/{locale}/` automatically overrides it (no code changes or extra configuration needed). To get a starting template, copy it with:

```
php artisan vendor:publish --tag=easy-auth-lang
```

## Audit Log

Every authentication attempt (password and passkey sign-in, sign-out, device mismatches) and every mutating operation from the events table above writes one structured entry to a dedicated log channel, so "who did what, when, with what result" can be reconstructed from disk after the fact — this is what the events table's `*ed` events feed into internally (`DoITs\EasyAuth\Listeners\AuditLogSubscriber`), plus `Illuminate\Auth\Events\Login`/`Failed`/`Logout` and `Laravel\Passkeys\Events\PasskeyRegistered`/`PasskeyVerified`/`PasskeyDeleted` for authentication itself.

The channel (`config('easy-auth.audit_log_channel')`, default `easy-auth-audit`) is registered automatically with a daily-rotating driver — `storage/logs/easy-auth-audit-*.log` — retained for `config('easy-auth.audit_log_retention_days')` days (default 30). There is no in-app viewer; read it directly (`tail -f storage/logs/easy-auth-audit-*.log`, `grep`, etc.), or define a channel of the same name in the app's own `logging.php` to route entries elsewhere (Papertrail, a database sink, whatever the app already uses) instead.

Each line is one JSON-ish log entry with `action` (e.g. `tenant_member.removed`, `auth.login`, `auth.failed`), `outcome` (`success`/`failure`), `actor`/`target` (`{id, name}`, when known), `tenant`, `ip`, `user_agent`, and `device_uuid`. Entries never include credentials or an attempted-but-wrong email address — `auth.failed` (bad credentials) and `auth.device_mismatch` (a correct password from a device the account isn't bound to) record only the outcome and request metadata, the same "don't confirm whether an email exists" posture `SignInController`'s generic `sign_in_failed` message already takes.

## Known Limitations and Future Considerations

- The `EnsureProfileIsComplete` middleware determines profile completeness solely from `$user->name === ''`. If the app adds its own required profile fields (e.g. a phone number), this middleware is unaware of them and lets the request through regardless.
- The WebAuthn options used at passkey registration (`userVerification`, `residentKey`) are left at `laravel/passkeys`'s defaults (both `required`); easy-auth exposes no setting to override them.
- A feature to check the authenticator's BE (Backup Eligible) flag at registration and reject passkeys that can sync to the cloud (e.g. via iCloud Keychain across multiple devices) is provided as `DoITs\EasyAuth\Actions\RejectSyncedPasskey`. Device-UUID binding is the proof that "this is the invited device," and a syncable passkey can weaken that guarantee (by letting someone pass the identity check on a device outside the organization at invitation-redemption time). However, enabling this rejection was found, on real hardware, to make registration entirely impossible in environments where a browser password-manager extension (e.g. 1Password) effectively crowds the platform authenticator (Windows Hello, etc.) out of the OS's passkey-selection dialog, leaving no way to create a new device-only passkey. Since easy-auth intentionally has no email/password fallback, this dead end was judged worse than the risk of the organization boundary being weakened by synced passkeys, so `config('easy-auth.reject_backup_eligible_passkeys')` (`.env`'s `EASY_AUTH_REJECT_BACKUP_ELIGIBLE_PASSKEYS`) defaults to off. A per-service switch from off to on is provided (`php artisan vendor:publish --tag=easy-auth-config`, or override via `.env`).
- A feature to force `authenticatorAttachment` to `platform` at registration, excluding cross-platform authenticators and password managers from the selection dialog itself, is provided via `config('easy-auth.force_platform_authenticator')` (`.env`'s `EASY_AUTH_FORCE_PLATFORM_AUTHENTICATOR`). It defaults to off, and the plan is to keep it off going forward. Real-hardware testing on Windows 11 + 1Password found that forcing `authenticatorAttachment: platform` did not remove 1Password from the selection dialog — it remained selectable, and choosing it then hit the `reject_backup_eligible_passkeys` BE rejection, blocking completion. Since this setting's intended effect of "narrowing the choice down to Hello only" was confirmed not to hold for some browser/extension combinations, there was no compelling reason to turn it on, so off was kept as both the default and the final conclusion.
- Allowing an invitation's "maximum uses" to be set above 1 (letting a single invitation link be reused by multiple people) is provided via `config('easy-auth.multi_use_invitations')` (`.env`'s `EASY_AUTH_MULTI_USE_INVITATIONS`), defaulting to off. The device-UUID-binding and invitation-chain design assumes implicit tracking of "who invited whom," and an invitation redeemable multiple times weakens that. This setting also calls for a system-administrator-level judgment call, and was judged not to be something an ordinary tenant admin or member should be able to freely toggle. While off, the "maximum uses" field itself is removed from the invitation form, and the server forces `max_uses=1` (single use) regardless of any submitted value.
- Allowing an invitation's expiry to be set to a custom time (or left blank for no expiry at all) is provided via `config('easy-auth.custom_invitation_expiration')` (`.env`'s `EASY_AUTH_CUSTOM_INVITATION_EXPIRATION`), defaulting to off. easy-auth's whole premise is a face-to-face invitation chain that spreads through an organization in a short window, and a long-lived or non-expiring invitation link works against that. While off, the "expires at" field itself is removed from the invitation form, and the server forces every invitation to expire `Invitation::DEFAULT_EXPIRATION_MINUTES` (30 minutes) from creation regardless of any submitted value.

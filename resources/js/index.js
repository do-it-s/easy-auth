import { Passkeys } from '@laravel/passkeys';

/**
 * Tells the host app whether a returning-user sign-in affordance is worth
 * showing on its own guest home view. A `device_uuid` is the only local
 * signal available (WebAuthn has no privacy-safe way to ask "does this
 * browser have a credential for this site" without an actual ceremony),
 * so this is a heuristic, not a guarantee that attemptSignIn() will succeed.
 */
export function canAttemptSignIn() {
    const deviceUuid = localStorage.getItem('device_uuid');

    if (!deviceUuid) {
        return false;
    }

    return localStorage.getItem('auth_method') === 'password' || Passkeys.isSupported();
}

/**
 * Attempts to sign in this device's previously-registered user. Must only
 * be called in direct response to an explicit user action (e.g. a click) —
 * WebAuthn ceremonies should never be triggered automatically. Performs no
 * DOM writes and no navigation; the caller decides what to show/do next.
 *
 * Resolves with one of three outcomes (never rejects except for genuinely
 * unexpected errors, which callers are not expected to special-case):
 * - { outcome: 'success', redirect }: session is already established.
 * - { outcome: 'failure' }: the ceremony didn't succeed (cancelled, no
 *   matching credential, unsupported browser, or server-side rejection).
 * - { outcome: 'fallback' }: this device is a password-fallback user, so
 *   no ceremony was attempted; the caller should route to the password
 *   sign-in page instead.
 */
export async function attemptSignIn() {
    if (localStorage.getItem('auth_method') === 'password') {
        return { outcome: 'fallback' };
    }

    if (!Passkeys.isSupported()) {
        return { outcome: 'failure' };
    }

    Passkeys.configure({
        fetch: {
            headers: {
                'X-Device-Uuid': localStorage.getItem('device_uuid') ?? '',
            },
        },
    });

    try {
        const result = await Passkeys.verify();

        return { outcome: 'success', redirect: result.redirect ?? '/' };
    } catch (error) {
        // Logged rather than swallowed: 'failure' also covers unexpected
        // errors (network, server rejection), which are otherwise
        // undebuggable since the caller only sees the outcome string.
        console.error(error);

        return { outcome: 'failure' };
    }
}

/**
 * Returns this device's locally-stored sign-in identifiers. Read-only,
 * performs no DOM access; callers decide how/where to display them.
 */
export function getDeviceCredentials() {
    return {
        device_uuid: localStorage.getItem('device_uuid'),
        auth_method: localStorage.getItem('auth_method'),
    };
}

/**
 * Clears this device's locally-stored sign-in identifiers (e.g. after the
 * account they pointed to was deleted, or on user request).
 */
export function clearDeviceCredentials() {
    localStorage.removeItem('device_uuid');
    localStorage.removeItem('auth_method');
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/**
 * Posts JSON to the given URL and classifies the outcome without ever
 * throwing: primitives built on this can return a plain { outcome, code }
 * shape instead of forcing every caller to catch exceptions. A response
 * body containing `errors` is assumed to already be a Laravel validation
 * response (field messages are pre-translated server-side); anything else
 * that isn't `response.ok` is a `server_error`, and a request that never
 * reached/parsed a response is a `network_error`.
 */
async function postJson(url, body, { method = 'POST', headers = {} } = {}) {
    let response;

    try {
        response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                ...headers,
            },
            body: JSON.stringify(body),
        });
    } catch (error) {
        console.error(error);

        return { ok: false, code: 'network_error' };
    }

    let data;

    try {
        data = await response.json();
    } catch (error) {
        console.error(error);

        return { ok: false, code: 'network_error' };
    }

    if (!response.ok) {
        return {
            ok: false,
            code: data.errors ? 'validation' : 'server_error',
            errors: data.errors,
        };
    }

    return { ok: true, data };
}

/**
 * Registers a passkey for a not-yet-created user and saves their display
 * name. No DOM reads/writes; callers decide what to show/do with the
 * outcome. `passkeyLabel` is the passkey's own nickname (what
 * @laravel/passkeys stores for this credential, e.g. "MacBook Pro"), not
 * the user's display name — kept separate so callers can supply their own
 * translated default without this module hardcoding one.
 */
export async function registerPasskey({ name, passkeyLabel }) {
    const trimmedName = (name ?? '').trim();

    // HTML's `required` only rejects a truly empty value, so whitespace-only
    // input would otherwise reach passkeyOptions() and silently become a
    // placeholder WebAuthn display name instead of the name the user meant
    // to register.
    if (!trimmedName) {
        return { outcome: 'failure', code: 'name_required' };
    }

    let registration;

    try {
        registration = await Passkeys.register({
            name: passkeyLabel,
            routes: {
                options: `/profile/passkey-options?name=${encodeURIComponent(trimmedName)}`,
                submit: '/profile',
            },
        });
    } catch (error) {
        console.error(error);

        return { outcome: 'failure', code: 'ceremony_failed' };
    }

    if (registration.device_uuid) {
        localStorage.setItem('device_uuid', registration.device_uuid);
    }

    const result = await postJson('/profile', { name: trimmedName }, { method: 'PATCH' });

    if (!result.ok) {
        return { outcome: 'failure', code: result.code, errors: result.errors };
    }

    return { outcome: 'success', redirect: result.data.redirect ?? '/' };
}

/**
 * Registers a new user with an email and password, for devices where
 * passkeys are not available.
 */
export async function registerWithPassword({ name, email, password, password_confirmation }) {
    const result = await postJson('/profile-password', { name, email, password, password_confirmation });

    if (!result.ok) {
        return { outcome: 'failure', code: result.code, errors: result.errors };
    }

    if (result.data.device_uuid) {
        localStorage.setItem('device_uuid', result.data.device_uuid);
        localStorage.setItem('auth_method', 'password');
    }

    return { outcome: 'success', redirect: result.data.redirect ?? '/' };
}

/**
 * Signs in using an email and password (this device's password-fallback
 * counterpart to attemptSignIn()).
 */
export async function signInWithPassword({ email, password }) {
    const result = await postJson(
        '/login',
        { email, password },
        { headers: { 'X-Device-Uuid': localStorage.getItem('device_uuid') ?? '' } },
    );

    if (!result.ok) {
        return { outcome: 'failure', code: result.code, errors: result.errors };
    }

    return { outcome: 'success', redirect: result.data.redirect ?? '/' };
}

function readStrings() {
    const el = document.getElementById('easy-auth-strings');

    if (!el) {
        return {};
    }

    try {
        return JSON.parse(el.textContent);
    } catch (error) {
        console.error(error);

        return {};
    }
}

function joinErrors(errors) {
    return Object.values(errors ?? {}).flat().join(' ');
}

/**
 * Wires up the passkey registration/sign-in and password-fallback forms
 * rendered by this package's own Blade views, plus the device-reset and
 * account-deletion pages' localStorage housekeeping. The host application's
 * own JS entrypoint should import and call this once after Alpine (or
 * whatever else it bootstraps) is set up; every element ID referenced here
 * is owned by this package's own views, not by the host app, and is looked
 * up defensively since any given page renders only some of them.
 *
 * By default, failures are written into this package's own status elements
 * (`#passkey-status`, `#sign-in-status`). Pass `onStatus({ outcome, code,
 * message })` to take over that display yourself (e.g. a toast) instead —
 * the form wiring and WebAuthn ceremony calls stay handled by this
 * function either way. Apps that don't want even the wiring/element IDs
 * should call the exported primitives (registerPasskey, attemptSignIn,
 * etc.) directly instead of calling initEasyAuth() at all.
 */
export function initEasyAuth({ onStatus } = {}) {
    const strings = readStrings();

    const profileCreateForm = document.getElementById('profile-create-form');
    const showPasswordRegisterButton = document.getElementById('show-password-register');
    const passwordRegisterForm = document.getElementById('password-register-form');
    const showLeaveTenantButton = document.getElementById('show-leave-tenant');
    const leaveTenantSection = document.getElementById('leave-tenant-section');
    const signInForm = document.getElementById('sign-in-form');
    const nameInput = document.getElementById('name');
    const registerNameInput = document.getElementById('register-name');
    const passkeyStatus = document.getElementById('passkey-status');
    const signInStatus = document.getElementById('sign-in-status');
    const deviceUuidEl = document.getElementById('device-uuid');
    const authMethodEl = document.getElementById('auth-method');
    const deviceResetStatus = document.getElementById('status');
    const clearDeviceButton = document.getElementById('clear');

    function report(el, outcome, code, message) {
        if (onStatus) {
            onStatus({ outcome, code, message });

            return;
        }

        if (el) {
            el.textContent = message ?? '';
        }
    }

    function showPasswordRegisterForm() {
        profileCreateForm?.classList.add('hidden');
        showPasswordRegisterButton?.classList.add('hidden');
        passwordRegisterForm?.classList.remove('hidden');

        if (registerNameInput && !registerNameInput.value && nameInput?.value) {
            registerNameInput.value = nameInput.value;
        }

        if (passkeyStatus) {
            passkeyStatus.textContent = '';
        }
    }

    // Browsers without passkey support go straight to the password form.
    if (profileCreateForm && !Passkeys.isSupported()) {
        showPasswordRegisterForm();
    }

    showPasswordRegisterButton?.addEventListener('click', () => showPasswordRegisterForm());

    showLeaveTenantButton?.addEventListener('click', () => {
        showLeaveTenantButton.classList.add('hidden');
        leaveTenantSection?.classList.remove('hidden');
    });

    profileCreateForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const name = new FormData(profileCreateForm).get('name');
        const result = await registerPasskey({ name, passkeyLabel: strings.passkeyLabel });

        if (result.outcome === 'success') {
            report(passkeyStatus, 'success', undefined, undefined);
            window.location.href = result.redirect;

            return;
        }

        const messages = {
            name_required: strings.nameRequired,
            ceremony_failed: strings.passkeyRegistrationFailed,
            validation: joinErrors(result.errors),
            server_error: strings.profileSaveFailed,
            network_error: strings.networkError,
        };

        report(passkeyStatus, 'failure', result.code, messages[result.code]);
        showPasswordRegisterButton?.classList.remove('hidden');
    });

    passwordRegisterForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = Object.fromEntries(new FormData(passwordRegisterForm));
        const result = await registerWithPassword(formData);

        if (result.outcome === 'success') {
            report(passkeyStatus, 'success', undefined, undefined);
            window.location.href = result.redirect;

            return;
        }

        const messages = {
            validation: joinErrors(result.errors),
            server_error: strings.passwordRegistrationFailed,
            network_error: strings.networkError,
        };

        report(passkeyStatus, 'failure', result.code, messages[result.code]);
    });

    signInForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = Object.fromEntries(new FormData(signInForm));
        const result = await signInWithPassword(formData);

        if (result.outcome === 'success') {
            report(signInStatus, 'success', undefined, undefined);
            window.location.href = result.redirect;

            return;
        }

        const messages = {
            validation: joinErrors(result.errors),
            server_error: strings.signInFailed,
            network_error: strings.networkError,
        };

        report(signInStatus, 'failure', result.code, messages[result.code]);
    });

    if (deviceUuidEl && authMethodEl) {
        const credentials = getDeviceCredentials();

        deviceUuidEl.textContent = credentials.device_uuid ?? strings.deviceNone;
        authMethodEl.textContent = credentials.auth_method ?? strings.deviceNone;

        clearDeviceButton?.addEventListener('click', () => {
            clearDeviceCredentials();

            deviceUuidEl.textContent = strings.deviceNone;
            authMethodEl.textContent = strings.deviceNone;

            if (deviceResetStatus) {
                deviceResetStatus.textContent = [strings.deviceCleared, strings.deviceNextStep].join(' ');
            }
        });
    }

    if (document.getElementById('account-deleted-page')) {
        clearDeviceCredentials();
    }
}

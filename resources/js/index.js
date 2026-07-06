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
 * Wires up the passkey registration/sign-in and password-fallback forms
 * rendered by this package's own Blade views. The host application's
 * own JS entrypoint should import and call this once after Alpine (or
 * whatever else it bootstraps) is set up; the IDs and routes referenced
 * here are owned by this package, not by the host app.
 */
export function initEasyAuth() {
    const profileCreateForm = document.getElementById('profile-create-form');
    const showPasswordRegisterButton = document.getElementById('show-password-register');
    const passwordRegisterForm = document.getElementById('password-register-form');
    const showLeaveTenantButton = document.getElementById('show-leave-tenant');
    const leaveTenantSection = document.getElementById('leave-tenant-section');
    const signInForm = document.getElementById('sign-in-form');
    const nameInput = document.getElementById('name');
    const registerNameInput = document.getElementById('register-name');
    const status = document.getElementById('passkey-status');
    const signInStatus = document.getElementById('sign-in-status');

    function showPasswordRegisterForm() {
        profileCreateForm?.classList.add('hidden');
        showPasswordRegisterButton?.classList.add('hidden');
        passwordRegisterForm?.classList.remove('hidden');

        if (registerNameInput && !registerNameInput.value && nameInput?.value) {
            registerNameInput.value = nameInput.value;
        }

        if (status) {
            status.textContent = '';
        }
    }

    // パスキーに対応していないブラウザでは、最初からメール+パスワードでの登録フォームを表示する。
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

        const name = (new FormData(profileCreateForm).get('name') ?? '').trim();

        // HTML's `required` only rejects a truly empty value, so
        // whitespace-only input would otherwise reach passkeyOptions() and
        // silently become a placeholder WebAuthn display name instead of
        // the name the user meant to register.
        if (!name) {
            if (status) {
                status.textContent = '名前を入力してください';
            }

            return;
        }

        try {
            const result = await Passkeys.register({
                name: 'このデバイス',
                routes: {
                    options: `/profile/passkey-options?name=${encodeURIComponent(name)}`,
                    submit: '/profile',
                },
            });

            if (result.device_uuid) {
                localStorage.setItem('device_uuid', result.device_uuid);
            }

            const response = await fetch('/profile', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                },
                body: JSON.stringify({ name }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message ?? 'プロフィールの保存に失敗しました');
            }

            window.location.href = data.redirect ?? '/';
        } catch (error) {
            if (status) {
                status.textContent = `登録に失敗しました: ${error.message}`;
            }

            showPasswordRegisterButton?.classList.remove('hidden');
        }
    });

    passwordRegisterForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(passwordRegisterForm);

        try {
            const response = await fetch('/profile-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                },
                body: JSON.stringify(Object.fromEntries(formData)),
            });

            const data = await response.json();

            if (!response.ok) {
                const message = data.errors
                    ? Object.values(data.errors).flat().join(' ')
                    : (data.message ?? '登録に失敗しました');

                throw new Error(message);
            }

            if (data.device_uuid) {
                localStorage.setItem('device_uuid', data.device_uuid);
                localStorage.setItem('auth_method', 'password');
            }

            window.location.href = data.redirect ?? '/';
        } catch (error) {
            if (status) {
                status.textContent = `登録に失敗しました: ${error.message}`;
            }
        }
    });

    signInForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(signInForm);

        try {
            const response = await fetch('/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    'X-Device-Uuid': localStorage.getItem('device_uuid') ?? '',
                },
                body: JSON.stringify(Object.fromEntries(formData)),
            });

            const data = await response.json();

            if (!response.ok) {
                const message = data.errors
                    ? Object.values(data.errors).flat().join(' ')
                    : (data.message ?? 'サインインに失敗しました');

                throw new Error(message);
            }

            window.location.href = data.redirect ?? '/';
        } catch (error) {
            if (signInStatus) {
                signInStatus.textContent = error.message;
            }
        }
    });
}

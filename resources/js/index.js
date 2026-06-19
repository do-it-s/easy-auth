import { Passkeys } from '@laravel/passkeys';

/**
 * Wires up the passkey registration/login and password-fallback forms
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
    const loginForm = document.getElementById('login-form');
    const nameInput = document.getElementById('name');
    const registerNameInput = document.getElementById('register-name');
    const status = document.getElementById('passkey-status');
    const loginStatus = document.getElementById('login-status');
    const isAuthenticated = document.querySelector('meta[name="auth"]')?.getAttribute('content') === '1';

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

    async function loginWithPasskey({ silent = false, fallbackToLogin = false } = {}) {
        try {
            Passkeys.configure({
                fetch: {
                    headers: {
                        'X-Device-Uuid': localStorage.getItem('device_uuid') ?? '',
                    },
                },
            });

            const result = await Passkeys.verify();

            window.location.href = result.redirect ?? '/';
        } catch (error) {
            if (fallbackToLogin) {
                window.location.href = '/login';

                return;
            }

            if (status && !silent) {
                status.textContent = `ログインに失敗しました: ${error.message}`;
            }
        }
    }

    // 未ログインかつこのデバイスに登録済みの場合は、トップページへのアクセス時に限り自動でログインを試行する。
    if (!isAuthenticated && window.location.pathname === '/' && localStorage.getItem('device_uuid')) {
        if (localStorage.getItem('auth_method') === 'password') {
            window.location.href = '/login';
        } else if (Passkeys.isSupported()) {
            loginWithPasskey({ silent: true, fallbackToLogin: true });
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

        const name = new FormData(profileCreateForm).get('name');

        try {
            const result = await Passkeys.register({
                name: 'このデバイス',
                routes: {
                    options: '/profile/passkey-options',
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

    loginForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(loginForm);

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
                    : (data.message ?? 'ログインに失敗しました');

                throw new Error(message);
            }

            window.location.href = data.redirect ?? '/';
        } catch (error) {
            if (loginStatus) {
                loginStatus.textContent = error.message;
            }
        }
    });
}

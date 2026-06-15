<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-[#FDFDFC] text-[#1b1b18] flex p-6 items-center justify-center min-h-screen flex-col">
        <div class="w-full max-w-sm">
            <h1 class="mb-4 text-lg">このデバイスの登録情報をリセット</h1>

            <p class="mb-4 text-sm">
                <span class="block">device_uuid: <span id="device-uuid"></span></span>
                <span class="block">auth_method: <span id="auth-method"></span></span>
            </p>

            <button
                id="clear"
                type="button"
                class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
            >
                クリアする
            </button>

            <a
                href="{{ route('home') }}"
                class="inline-block mt-4 px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
            >
                ホームへ戻る
            </a>
        </div>

        <script>
            const deviceUuidEl = document.getElementById('device-uuid');
            const authMethodEl = document.getElementById('auth-method');
            const statusEl = document.getElementById('status');

            deviceUuidEl.textContent = localStorage.getItem('device_uuid') ?? '(なし)';
            authMethodEl.textContent = localStorage.getItem('auth_method') ?? '(なし)';

            document.getElementById('clear').addEventListener('click', () => {
                localStorage.removeItem('device_uuid');
                localStorage.removeItem('auth_method');

                deviceUuidEl.textContent = '(なし)';
                authMethodEl.textContent = '(なし)';
                statusEl.textContent = 'クリアしました。';
            });
        </script>
    </body>
</html>

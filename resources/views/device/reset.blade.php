@extends('layouts.app')

@section('content')
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
    </div>
@endsection

@push('scripts')
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
@endpush

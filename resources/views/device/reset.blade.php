@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">{{ __('easy-auth::device.heading') }}</h1>

        <p class="mb-4 text-sm">{{ __('easy-auth::device.description') }}</p>

        <p class="mb-4 text-sm">
            <span class="block">device_uuid: <span id="device-uuid"></span></span>
            <span class="block">auth_method: <span id="auth-method"></span></span>
        </p>

        <button
            id="clear"
            type="button"
            class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
        >
            {{ __('easy-auth::device.clear_button') }}
        </button>

        <p id="status" class="mt-4 text-sm"></p>
    </div>
@endsection

@push('scripts')
    <script>
        const deviceUuidEl = document.getElementById('device-uuid');
        const authMethodEl = document.getElementById('auth-method');
        const statusEl = document.getElementById('status');
        const noneLabel = @json(__('easy-auth::device.none'));
        const clearedLabel = @json(__('easy-auth::device.cleared'));
        const nextStepLabel = @json(__('easy-auth::device.next_step'));

        deviceUuidEl.textContent = localStorage.getItem('device_uuid') ?? noneLabel;
        authMethodEl.textContent = localStorage.getItem('auth_method') ?? noneLabel;

        document.getElementById('clear').addEventListener('click', () => {
            localStorage.removeItem('device_uuid');
            localStorage.removeItem('auth_method');

            deviceUuidEl.textContent = noneLabel;
            authMethodEl.textContent = noneLabel;
            statusEl.textContent = [clearedLabel, nextStepLabel].join(' ');
        });
    </script>
@endpush

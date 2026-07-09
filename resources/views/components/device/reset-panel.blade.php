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

@push('scripts')
    @include('easy-auth::partials.js-strings', ['strings' => [
        'deviceNone' => __('easy-auth::device.none'),
        'deviceCleared' => __('easy-auth::device.cleared'),
        'deviceNextStep' => __('easy-auth::device.next_step'),
    ]])
@endpush

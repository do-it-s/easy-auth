<p id="passkey-status" class="mb-4 text-sm text-red-600"></p>

<form id="profile-create-form">
    <label for="name" class="block mb-1 text-sm">{{ __('easy-auth::profile.name') }}</label>

    <input
        id="name"
        name="name"
        type="text"
        required
        class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
    >

    @stack('easy-auth::components.profile.passkey-registration-form.after-fields')

    <button
        type="submit"
        class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
    >
        {{ __('easy-auth::profile.register_with_passkey') }}
    </button>
</form>

<button
    id="show-password-register"
    type="button"
    class="hidden mt-4 text-sm underline"
>
    {{ __('easy-auth::profile.passkey_unavailable') }}
</button>

@push('scripts')
    @include('easy-auth::partials.js-strings', ['strings' => [
        'nameRequired' => __('easy-auth::profile.name_required'),
        'passkeyLabel' => __('easy-auth::profile.passkey_label'),
        'passkeyRegistrationFailed' => __('easy-auth::profile.passkey_register_failed'),
        'profileSaveFailed' => __('easy-auth::profile.save_failed'),
        'networkError' => __('easy-auth::profile.network_error'),
    ]])
@endpush

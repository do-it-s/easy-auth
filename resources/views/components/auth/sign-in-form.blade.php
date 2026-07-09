<div class="w-full max-w-sm">
    <h1 class="mb-4 text-lg">{{ __('easy-auth::auth.sign_in_heading') }}</h1>

    @if (session('status'))
        <p class="mb-4 text-sm text-green-600">{{ session('status') }}</p>
    @endif

    <p id="sign-in-status" class="mb-4 text-sm text-red-600"></p>

    <form id="sign-in-form">
        <label for="email" class="block mb-1 text-sm">{{ __('easy-auth::auth.email') }}</label>

        <input
            id="email"
            name="email"
            type="email"
            required
            class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
        >

        <label for="password" class="block mb-1 text-sm">{{ __('easy-auth::auth.password') }}</label>

        <input
            id="password"
            name="password"
            type="password"
            required
            class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
        >

        @stack('easy-auth::components.auth.sign-in-form.after-fields')

        <button
            type="submit"
            class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
        >
            {{ __('easy-auth::auth.sign_in_button') }}
        </button>
    </form>

    <p class="mt-4 text-sm">
        <a href="{{ route('password.request') }}" class="underline">
            {{ __('easy-auth::auth.forgot_password_link') }}
        </a>
    </p>
</div>

@push('scripts')
    @include('easy-auth::partials.js-strings', ['strings' => [
        'signInFailed' => __('easy-auth::auth.sign_in_request_failed'),
        'networkError' => __('easy-auth::auth.network_error'),
    ]])
@endpush

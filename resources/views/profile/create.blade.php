@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">{{ __('easy-auth::profile.create_heading') }}</h1>

        <form id="profile-create-form">
            <label for="name" class="block mb-1 text-sm">{{ __('easy-auth::profile.name') }}</label>

            <input
                id="name"
                name="name"
                type="text"
                required
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
            >

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

        <form id="password-register-form" class="hidden mt-4">
            <label for="register-name" class="block mb-1 text-sm">{{ __('easy-auth::profile.register_name') }}</label>

            <input
                id="register-name"
                name="name"
                type="text"
                required
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
            >

            <label for="register-email" class="block mb-1 text-sm">{{ __('easy-auth::profile.register_email') }}</label>

            <input
                id="register-email"
                name="email"
                type="email"
                required
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
            >

            <label for="register-password" class="block mb-1 text-sm">{{ __('easy-auth::profile.register_password') }}</label>

            <input
                id="register-password"
                name="password"
                type="password"
                required
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
            >

            <label for="register-password-confirmation" class="block mb-1 text-sm">{{ __('easy-auth::profile.register_password_confirmation') }}</label>

            <input
                id="register-password-confirmation"
                name="password_confirmation"
                type="password"
                required
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
            >

            <button
                type="submit"
                class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
            >
                {{ __('easy-auth::profile.register_with_password') }}
            </button>
        </form>
    </div>
@endsection

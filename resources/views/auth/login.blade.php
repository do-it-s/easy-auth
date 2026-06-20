@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">{{ __('easy-auth::auth.login_heading') }}</h1>

        <p id="login-status" class="mb-4 text-sm text-red-600"></p>

        <form id="login-form">
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

            <button
                type="submit"
                class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
            >
                {{ __('easy-auth::auth.login_button') }}
            </button>
        </form>

        <p id="device-reset-link" class="hidden mt-4 text-sm">
            <a href="{{ route('device.reset') }}" class="underline">
                {{ __('easy-auth::auth.cant_login_link') }}
            </a>
        </p>
    </div>
@endsection

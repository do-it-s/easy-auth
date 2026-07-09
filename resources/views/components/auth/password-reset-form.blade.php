@props(['token', 'email'])

<div class="w-full max-w-sm">
    <h1 class="mb-4 text-lg">{{ __('easy-auth::password_reset.reset_heading') }}</h1>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <label for="email" class="block mb-1 text-sm">{{ __('easy-auth::auth.email') }}</label>

        <input
            id="email"
            name="email"
            type="email"
            required
            value="{{ old('email', $email) }}"
            class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
        >

        @error('email')
            <p class="mb-4 text-sm text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
        @enderror

        <label for="password" class="block mb-1 text-sm">{{ __('easy-auth::auth.password') }}</label>

        <input
            id="password"
            name="password"
            type="password"
            required
            class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
        >

        @error('password')
            <p class="mb-4 text-sm text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
        @enderror

        <label for="password_confirmation" class="block mb-1 text-sm">{{ __('easy-auth::profile.register_password_confirmation') }}</label>

        <input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            required
            class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
        >

        @stack('easy-auth::components.auth.password-reset-form.after-fields')

        <button
            type="submit"
            class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
        >
            {{ __('easy-auth::password_reset.reset_button') }}
        </button>
    </form>
</div>

<div class="w-full max-w-sm">
    <h1 class="mb-4 text-lg">{{ __('easy-auth::password_reset.request_heading') }}</h1>

    <p class="mb-4 text-sm">{{ __('easy-auth::password_reset.request_description') }}</p>

    @if (session('status'))
        <p class="mb-4 text-sm text-green-600">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <label for="email" class="block mb-1 text-sm">{{ __('easy-auth::auth.email') }}</label>

        <input
            id="email"
            name="email"
            type="email"
            required
            value="{{ old('email') }}"
            class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
        >

        @error('email')
            <p class="mb-4 text-sm text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
        @enderror

        @stack('easy-auth::components.auth.password-request-form.after-fields')

        <button
            type="submit"
            class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
        >
            {{ __('easy-auth::password_reset.request_button') }}
        </button>
    </form>
</div>

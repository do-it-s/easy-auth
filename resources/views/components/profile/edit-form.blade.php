@props(['user'])

<div class="w-full max-w-sm">
    <h1 class="mb-4 text-lg">{{ __('easy-auth::profile.edit_heading') }}</h1>

    <x-easy-auth::shared.status-message />

    @error('name')
        <p class="mb-4 text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route('profile.update') }}">
        @csrf
        @method('PATCH')

        <label for="name" class="block mb-1 text-sm">{{ __('easy-auth::profile.name') }}</label>

        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $user->name) }}"
            class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
        >

        @stack('easy-auth::components.profile.edit-form.after-fields')

        <button
            type="submit"
            class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
        >
            {{ __('easy-auth::profile.save') }}
        </button>
    </form>
</div>

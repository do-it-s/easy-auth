@props(['tenant'])

<div class="w-full max-w-sm">
    <h1 class="mb-4 text-lg">{{ __('easy-auth::tenants.edit_heading') }}</h1>

    <x-easy-auth::shared.status-message />

    @error('tenant_name')
        <p class="mb-4 text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route('tenants.update', $tenant) }}">
        @csrf
        @method('PATCH')

        <label for="tenant_name" class="block mb-1 text-sm">{{ __('easy-auth::tenants.name') }}</label>

        <input
            id="tenant_name"
            name="tenant_name"
            type="text"
            value="{{ old('tenant_name', $tenant->name) }}"
            class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
        >

        <label class="flex items-center gap-2 mb-4 text-sm">
            <input
                type="checkbox"
                name="member_invites_enabled"
                value="1"
                @checked(old('member_invites_enabled', $tenant->member_invites_enabled))
            >
            {{ __('easy-auth::tenants.member_invites_enabled') }}
        </label>

        @stack('easy-auth::components.tenants.edit-form.after-fields')

        <button
            type="submit"
            class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
        >
            {{ __('easy-auth::tenants.save') }}
        </button>
    </form>
</div>

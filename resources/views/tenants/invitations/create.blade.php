@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">{{ __('easy-auth::invitations.create_heading', ['tenant' => $tenant->name]) }}</h1>

        @if ($invitationUrl)
            <div class="mb-4 p-3 border border-[#19140035] rounded-sm text-sm">
                <p class="mb-2">{{ __('easy-auth::invitations.issued_notice') }}</p>
                <div class="mb-2 flex items-center gap-2">
                    <p class="break-all flex-1">{{ $invitationUrl }}</p>
                    <button
                        type="button"
                        class="js-copy-invitation-url shrink-0 px-2 py-1 border border-[#19140035] hover:border-[#19140035] rounded-sm text-xs leading-normal"
                        data-url="{{ $invitationUrl }}"
                        data-label="{{ __('easy-auth::invitations.copy_button') }}"
                        data-label-copied="{{ __('easy-auth::invitations.copy_done') }}"
                    >{{ __('easy-auth::invitations.copy_button') }}</button>
                </div>
                <img src="{{ $invitationQrCode }}" alt="{{ __('easy-auth::invitations.qr_alt') }}" class="w-40 h-40">
            </div>

            <script>
                document.querySelectorAll('.js-copy-invitation-url').forEach((button) => {
                    button.addEventListener('click', () => {
                        navigator.clipboard.writeText(button.dataset.url).then(() => {
                            button.textContent = button.dataset.labelCopied;
                            setTimeout(() => { button.textContent = button.dataset.label; }, 1500);
                        });
                    });
                });
            </script>
        @endif

        @error('role')
            <p class="mb-4 text-[#F53003]">{{ $message }}</p>
        @enderror

        @error('expires_at')
            <p class="mb-4 text-[#F53003]">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ route('tenants.invitations.store', $tenant) }}">
            @csrf

            <input type="hidden" name="role" value="{{ \DoITs\EasyAuth\Models\Tenant::MEMBER_ROLE }}">

            <label for="label" class="block mb-1 text-sm">{{ __('easy-auth::invitations.label') }}</label>

            <input
                id="label"
                name="label"
                type="text"
                value="{{ old('label') }}"
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
            >

            <label for="expires_at" class="block mb-1 text-sm">{{ __('easy-auth::invitations.expires_at') }}</label>

            <input
                id="expires_at"
                name="expires_at"
                type="datetime-local"
                value="{{ old('expires_at', $defaultExpiresAt) }}"
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
            >

            @if ($isAdmin)
                <label class="flex items-center gap-2 mb-4 text-sm">
                    <input
                        type="checkbox"
                        name="role"
                        value="{{ \DoITs\EasyAuth\Models\Tenant::ADMIN_ROLE }}"
                        @checked(old('role') === \DoITs\EasyAuth\Models\Tenant::ADMIN_ROLE)
                    >
                    {{ __('easy-auth::invitations.invite_as_admin') }}
                </label>
            @endif

            <button
                type="submit"
                class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
            >
                {{ __('easy-auth::invitations.invite_button') }}
            </button>
        </form>
    </div>
@endsection

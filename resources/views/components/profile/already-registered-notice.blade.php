@if (session()->has('pending_invitation_token'))
    <div id="already-registered-notice" class="hidden mb-4 p-3 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm">
        <p class="mb-2">{{ __('easy-auth::profile.already_registered_notice') }}</p>
        <a
            href="{{ route('home') }}"
            class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
        >
            {{ __('easy-auth::profile.already_registered_go_home') }}
        </a>
        <button
            id="already-registered-reinvite"
            type="button"
            class="block mt-2 text-sm underline"
        >
            {{ __('easy-auth::profile.already_registered_reinvite') }}
        </button>
    </div>
@endif

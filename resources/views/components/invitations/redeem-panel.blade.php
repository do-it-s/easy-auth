@props(['status', 'invitation' => null, 'token' => null, 'alreadyMember' => false, 'isPromotion' => false])

<div class="w-full max-w-sm text-center">
    @if ($status === 'invalid')
        <p class="mb-4">{{ __('easy-auth::invitations.invalid') }}</p>

        @stack('easy-auth::components.invitations.redeem-panel.after-message')
    @elseif ($status === 'already_admin')
        <p class="mb-4">{{ __('easy-auth::invitations.already_admin', ['tenant' => $invitation->tenant->name]) }}</p>

        @stack('easy-auth::components.invitations.redeem-panel.after-message')
    @elseif ($alreadyMember)
        <p class="mb-4">
            @if ($isPromotion)
                {{ __('easy-auth::invitations.promote_confirm', ['tenant' => $invitation->tenant->name]) }}
            @else
                {{ __('easy-auth::invitations.refresh_confirm', ['tenant' => $invitation->tenant->name]) }}
            @endif
        </p>

        @stack('easy-auth::components.invitations.redeem-panel.after-message')

        <form method="POST" action="{{ route('invitations.redeem', $token) }}">
            @csrf

            <button
                type="submit"
                class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
            >
                {{ $isPromotion ? __('easy-auth::invitations.promote_button') : __('easy-auth::invitations.refresh_button') }}
            </button>
        </form>
    @else
        @php
            $roleLabel = match ($invitation->role) {
                \DoITs\EasyAuth\Models\Tenant::ADMIN_ROLE => __('easy-auth::invitations.role_admin'),
                \DoITs\EasyAuth\Models\Tenant::MEMBER_ROLE => __('easy-auth::invitations.role_member'),
                default => $invitation->role,
            };
        @endphp
        <p class="mb-4">
            {{ __('easy-auth::invitations.join_prompt', ['tenant' => $invitation->tenant->name, 'role' => $roleLabel]) }}
        </p>

        @stack('easy-auth::components.invitations.redeem-panel.after-message')

        <form method="POST" action="{{ route('invitations.redeem', $token) }}">
            @csrf

            <button
                type="submit"
                class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
            >
                {{ __('easy-auth::invitations.join_button') }}
            </button>
        </form>
    @endif
</div>

@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm text-center">
        @if ($status === 'invalid')
            <p class="mb-4">{{ __('easy-auth::invitations.invalid') }}</p>
        @elseif ($status === 'already_admin')
            <p class="mb-4">{{ __('easy-auth::invitations.already_admin', ['tenant' => $invitation->tenant->name]) }}</p>
        @elseif ($alreadyMember)
            <p class="mb-4">
                @if ($isPromotion)
                    {{ __('easy-auth::invitations.promote_confirm', ['tenant' => $invitation->tenant->name]) }}
                @else
                    {{ __('easy-auth::invitations.refresh_confirm', ['tenant' => $invitation->tenant->name]) }}
                @endif
            </p>

            <form method="POST" action="{{ route('invitations.redeem', $token) }}">
                @csrf

                <button
                    type="submit"
                    class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
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

            <form method="POST" action="{{ route('invitations.redeem', $token) }}">
                @csrf

                <button
                    type="submit"
                    class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
                >
                    {{ __('easy-auth::invitations.join_button') }}
                </button>
            </form>
        @endif
    </div>
@endsection

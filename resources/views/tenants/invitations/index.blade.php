@extends('layouts.app')

@section('content')
    <div class="w-full max-w-md">
        <h1 class="mb-4 text-lg">{{ __('easy-auth::invitations.index_heading', ['tenant' => $tenant->name]) }}</h1>

        @forelse ($invitations as $invitation)
            <div class="mb-2 p-3 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm">
                <p>
                    {{ __('easy-auth::invitations.role_label') }}
                    @if ($invitation->role === \DoITs\EasyAuth\Models\Tenant::ADMIN_ROLE)
                        {{ __('easy-auth::invitations.role_admin') }}
                    @elseif ($invitation->role === \DoITs\EasyAuth\Models\Tenant::MEMBER_ROLE)
                        {{ __('easy-auth::invitations.role_member') }}
                    @else
                        {{ $invitation->role }}
                    @endif
                </p>

                @if ($invitation->label)
                    <p>{{ __('easy-auth::invitations.note_label', ['label' => $invitation->label]) }}</p>
                @endif

                <p>
                    {{ __('easy-auth::invitations.expires_label') }}
                    {{ $invitation->expires_at?->format('Y-m-d H:i') ?? __('easy-auth::invitations.no_expiration') }}
                </p>

                <p>
                    {{ __('easy-auth::invitations.status_label') }}
                    @if ($invitation->isUsed())
                        {{ __('easy-auth::invitations.status_used') }}
                    @elseif ($invitation->isExpired())
                        {{ __('easy-auth::invitations.status_expired') }}
                    @else
                        {{ __('easy-auth::invitations.status_active') }}
                    @endif
                </p>

                @can('delete', $invitation)
                    @if ($invitation->isUsable())
                        <form method="POST" action="{{ route('tenants.invitations.destroy', [$tenant, $invitation]) }}" class="mt-2">
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                            >
                                {{ __('easy-auth::invitations.revoke') }}
                            </button>
                        </form>
                    @endif
                @endcan
            </div>
        @empty
            <p class="text-sm">{{ __('easy-auth::invitations.none_yet') }}</p>
        @endforelse
    </div>
@endsection

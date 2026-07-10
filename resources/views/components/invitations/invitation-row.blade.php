@props(['tenant', 'invitation'])

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
        @if ($invitation->isRevoked())
            {{ __('easy-auth::invitations.status_revoked') }}
        @elseif ($invitation->isUsed())
            {{ __('easy-auth::invitations.status_used') }}
        @elseif ($invitation->isExpired())
            {{ __('easy-auth::invitations.status_expired') }}
        @else
            {{ __('easy-auth::invitations.status_active') }}
        @endif
    </p>

    @if (config('easy-auth.multi_use_invitations'))
        <p>
            {{ __('easy-auth::invitations.uses_label') }}
            @if ($invitation->max_uses === null)
                {{ __('easy-auth::invitations.uses_unlimited', ['used' => $invitation->redemptions->count()]) }}
            @else
                {{ __('easy-auth::invitations.uses_used_of_max', ['used' => $invitation->redemptions->count(), 'max' => $invitation->max_uses]) }}
            @endif
        </p>
    @endif

    @if ($invitation->redemptions->isNotEmpty())
        <p class="mt-2">{{ __('easy-auth::invitations.redeemed_by_heading') }}</p>
        <ul class="list-disc list-inside">
            @foreach ($invitation->redemptions as $redemption)
                <li>{{ __('easy-auth::invitations.redeemed_by_entry', ['name' => $redemption->user->name, 'datetime' => $redemption->redeemed_at->format('Y-m-d H:i')]) }}</li>
            @endforeach
        </ul>
    @endif

    @can('delete', $invitation)
        @if (! $invitation->isRevoked() && ! $invitation->isExpired())
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

    @includeIf('vendor.easy-auth.invitations.invitation-row-actions', ['tenant' => $tenant, 'invitation' => $invitation])
</div>

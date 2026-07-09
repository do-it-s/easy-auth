@props(['tenant', 'invitations'])

<div class="w-full max-w-md">
    <h1 class="mb-4 text-lg">{{ __('easy-auth::invitations.index_heading', ['tenant' => $tenant->name]) }}</h1>

    @forelse ($invitations as $invitation)
        <x-easy-auth::invitations.invitation-row :tenant="$tenant" :invitation="$invitation" />
    @empty
        <p class="text-sm">{{ __('easy-auth::invitations.none_yet') }}</p>
    @endforelse
</div>

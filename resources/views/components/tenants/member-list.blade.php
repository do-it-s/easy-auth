@props(['tenant', 'admins', 'others', 'adminCount'])

<div class="w-full max-w-md">
    <h1 class="mb-4 text-lg">{{ __('easy-auth::members.heading', ['tenant' => $tenant->name]) }}</h1>

    @if ($errors->has('role'))
        <p class="mb-4 text-sm text-[#F53003] dark:text-[#FF4433]">{{ $errors->first('role') }}</p>
    @endif

    <h2 class="mb-2 text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wide">{{ __('easy-auth::members.admins_heading') }}</h2>

    @forelse ($admins as $member)
        <x-easy-auth::tenants.member-row
            :tenant="$tenant"
            :member="$member"
            :is-admin-section="true"
            :show-management-actions="$adminCount > 1"
        />
    @empty
        <p class="mb-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ __('easy-auth::members.no_admins') }}</p>
    @endforelse

    <h2 class="mt-4 mb-2 text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wide">{{ __('easy-auth::members.members_heading') }}</h2>

    @forelse ($others as $member)
        <x-easy-auth::tenants.member-row
            :tenant="$tenant"
            :member="$member"
            :is-admin-section="false"
        />
    @empty
        <p class="mb-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ __('easy-auth::members.no_members') }}</p>
    @endforelse
</div>

@props(['tenant', 'member', 'isAdminSection', 'showManagementActions' => true])

<div class="mb-2 p-3 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm">
    <p>{{ $member->name }}</p>
    <p class="text-[#706f6c] dark:text-[#A1A09A]">
        {{ __('easy-auth::members.last_active') }}
        {{ $member->pivot->last_accessed_at ? \Carbon\Carbon::parse($member->pivot->last_accessed_at)->format('Y-m-d H:i') : __('easy-auth::members.unknown') }}
    </p>

    @if ($showManagementActions)
        <div class="mt-2 flex gap-2">
            @can('updateMember', [$tenant, $member])
                <form method="POST" action="{{ route('tenants.members.update', [$tenant, $member]) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="role" value="{{ $isAdminSection ? \DoITs\EasyAuth\Models\Tenant::MEMBER_ROLE : \DoITs\EasyAuth\Models\Tenant::ADMIN_ROLE }}">
                    <button type="submit" class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm leading-normal hover:bg-[#19140012] dark:hover:bg-[#eeeeec]/10">
                        {{ $isAdminSection ? __('easy-auth::members.demote') : __('easy-auth::members.promote') }}
                    </button>
                </form>
            @endcan

            @can('removeMember', [$tenant, $member])
                <form method="POST" action="{{ route('tenants.members.destroy', [$tenant, $member]) }}" onsubmit="return confirm({{ json_encode(__('easy-auth::members.remove_confirm')) }})">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm leading-normal hover:bg-[#19140012] dark:hover:bg-[#eeeeec]/10">
                        {{ __('easy-auth::members.remove') }}
                    </button>
                </form>
            @endcan

            @includeIf('vendor.easy-auth.tenants.member-row-actions', ['tenant' => $tenant, 'member' => $member, 'isAdminSection' => $isAdminSection])
        </div>
    @endif
</div>

@extends('layouts.app')

@section('content')
    <div class="w-full max-w-md">
        <h1 class="mb-4 text-lg">{{ __('easy-auth::members.heading', ['tenant' => $tenant->name]) }}</h1>

        @if ($errors->has('role'))
            <p class="mb-4 text-sm text-[#F53003]">{{ $errors->first('role') }}</p>
        @endif

        <h2 class="mb-2 text-sm font-semibold text-[#706f6c] uppercase tracking-wide">{{ __('easy-auth::members.admins_heading') }}</h2>

        @forelse ($admins as $member)
            <div class="mb-2 p-3 border border-[#19140035] rounded-sm text-sm">
                <p>{{ $member->name }}</p>
                <p class="text-[#706f6c]">
                    {{ __('easy-auth::members.last_active') }}
                    {{ $member->pivot->last_accessed_at ? \Carbon\Carbon::parse($member->pivot->last_accessed_at)->format('Y-m-d H:i') : __('easy-auth::members.unknown') }}
                </p>

                @if ($adminCount > 1)
                    <div class="mt-2 flex gap-2">
                        @can('updateMember', [$tenant, $member])
                            <form method="POST" action="{{ route('tenants.members.update', [$tenant, $member]) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="role" value="{{ \DoITs\EasyAuth\Models\Tenant::MEMBER_ROLE }}">
                                <button type="submit" class="inline-block px-5 py-1.5 border border-[#19140035] rounded-sm text-sm leading-normal hover:bg-[#19140012]">
                                    {{ __('easy-auth::members.demote') }}
                                </button>
                            </form>
                        @endcan

                        @can('removeMember', [$tenant, $member])
                            <form method="POST" action="{{ route('tenants.members.destroy', [$tenant, $member]) }}" onsubmit="return confirm(@json(__('easy-auth::members.remove_confirm')))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-block px-5 py-1.5 border border-[#19140035] rounded-sm text-sm leading-normal hover:bg-[#19140012]">
                                    {{ __('easy-auth::members.remove') }}
                                </button>
                            </form>
                        @endcan
                    </div>
                @endif
            </div>
        @empty
            <p class="mb-2 text-sm text-[#706f6c]">{{ __('easy-auth::members.no_admins') }}</p>
        @endforelse

        <h2 class="mt-4 mb-2 text-sm font-semibold text-[#706f6c] uppercase tracking-wide">{{ __('easy-auth::members.members_heading') }}</h2>

        @forelse ($others as $member)
            <div class="mb-2 p-3 border border-[#19140035] rounded-sm text-sm">
                <p>{{ $member->name }}</p>
                <p class="text-[#706f6c]">
                    {{ __('easy-auth::members.last_active') }}
                    {{ $member->pivot->last_accessed_at ? \Carbon\Carbon::parse($member->pivot->last_accessed_at)->format('Y-m-d H:i') : __('easy-auth::members.unknown') }}
                </p>

                <div class="mt-2 flex gap-2">
                    @can('updateMember', [$tenant, $member])
                        <form method="POST" action="{{ route('tenants.members.update', [$tenant, $member]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="role" value="{{ \DoITs\EasyAuth\Models\Tenant::ADMIN_ROLE }}">
                            <button type="submit" class="inline-block px-5 py-1.5 border border-[#19140035] rounded-sm text-sm leading-normal hover:bg-[#19140012]">
                                {{ __('easy-auth::members.promote') }}
                            </button>
                        </form>
                    @endcan

                    @can('removeMember', [$tenant, $member])
                        <form method="POST" action="{{ route('tenants.members.destroy', [$tenant, $member]) }}" onsubmit="return confirm(@json(__('easy-auth::members.remove_confirm')))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-block px-5 py-1.5 border border-[#19140035] rounded-sm text-sm leading-normal hover:bg-[#19140012]">
                                {{ __('easy-auth::members.remove') }}
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        @empty
            <p class="mb-2 text-sm text-[#706f6c]">{{ __('easy-auth::members.no_members') }}</p>
        @endforelse
    </div>
@endsection

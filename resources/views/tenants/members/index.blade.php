@extends('layouts.app')

@section('content')
    <div class="w-full max-w-md">
        <h1 class="mb-4 text-lg">{{ $tenant->name }} のメンバー</h1>

        @if ($errors->has('role'))
            <p class="mb-4 text-sm text-[#F53003]">{{ $errors->first('role') }}</p>
        @endif

        <h2 class="mb-2 text-sm font-semibold text-[#706f6c] uppercase tracking-wide">管理者</h2>

        @forelse ($admins as $member)
            <div class="mb-2 p-3 border border-[#19140035] rounded-sm text-sm">
                <p>{{ $member->name }}</p>
                <p class="text-[#706f6c]">
                    最終利用:
                    {{ $member->pivot->last_accessed_at ? \Carbon\Carbon::parse($member->pivot->last_accessed_at)->format('Y-m-d H:i') : '不明' }}
                </p>

                @if ($adminCount > 1)
                    @can('updateMember', [$tenant, $member])
                        <form method="POST" action="{{ route('tenants.members.update', [$tenant, $member]) }}" class="mt-2">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="role" value="{{ \App\Models\Tenant::MEMBER_ROLE }}">
                            <button type="submit" class="inline-block px-5 py-1.5 border border-[#19140035] rounded-sm text-sm leading-normal hover:bg-[#19140012]">
                                降格
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        @empty
            <p class="mb-2 text-sm text-[#706f6c]">管理者はいません。</p>
        @endforelse

        <h2 class="mt-4 mb-2 text-sm font-semibold text-[#706f6c] uppercase tracking-wide">メンバー</h2>

        @forelse ($others as $member)
            <div class="mb-2 p-3 border border-[#19140035] rounded-sm text-sm">
                <p>{{ $member->name }}</p>
                <p class="text-[#706f6c]">
                    最終利用:
                    {{ $member->pivot->last_accessed_at ? \Carbon\Carbon::parse($member->pivot->last_accessed_at)->format('Y-m-d H:i') : '不明' }}
                </p>

                @can('updateMember', [$tenant, $member])
                    <form method="POST" action="{{ route('tenants.members.update', [$tenant, $member]) }}" class="mt-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="role" value="{{ \App\Models\Tenant::ADMIN_ROLE }}">
                        <button type="submit" class="inline-block px-5 py-1.5 border border-[#19140035] rounded-sm text-sm leading-normal hover:bg-[#19140012]">
                            昇格
                        </button>
                    </form>
                @endcan
            </div>
        @empty
            <p class="mb-2 text-sm text-[#706f6c]">メンバーはいません。</p>
        @endforelse
    </div>
@endsection

@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">{{ $tenant->name }} への招待</h1>

        @if ($invitationUrl)
            <div class="mb-4 p-3 border border-[#19140035] rounded-sm text-sm">
                <p class="mb-2">招待を発行しました。このURLまたはQRコードを共有してください（このページを離れると再表示できません）。</p>
                <p class="mb-2 break-all">{{ $invitationUrl }}</p>
                <img src="{{ $invitationQrCode }}" alt="招待QRコード" class="w-40 h-40">
            </div>
        @endif

        @error('role')
            <p class="mb-4 text-[#F53003]">{{ $message }}</p>
        @enderror

        @error('expires_at')
            <p class="mb-4 text-[#F53003]">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ route('tenants.invitations.store', $tenant) }}">
            @csrf

            <input type="hidden" name="role" value="{{ \App\Models\Tenant::MEMBER_ROLE }}">

            <label for="label" class="block mb-1 text-sm">メモ（任意）</label>

            <input
                id="label"
                name="label"
                type="text"
                value="{{ old('label') }}"
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
            >

            <label for="expires_at" class="block mb-1 text-sm">有効期限（空欄の場合は無期限）</label>

            <input
                id="expires_at"
                name="expires_at"
                type="datetime-local"
                value="{{ old('expires_at', $defaultExpiresAt) }}"
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
            >

            @if ($isAdmin)
                <label class="flex items-center gap-2 mb-4 text-sm">
                    <input
                        type="checkbox"
                        name="role"
                        value="{{ \App\Models\Tenant::ADMIN_ROLE }}"
                        @checked(old('role') === \App\Models\Tenant::ADMIN_ROLE)
                    >
                    管理者として招待
                </label>
            @endif

            <button
                type="submit"
                class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
            >
                招待
            </button>
        </form>
    </div>
@endsection

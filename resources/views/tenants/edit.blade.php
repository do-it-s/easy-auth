@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">テナント設定</h1>

        @error('name')
            <p class="mb-4 text-[#F53003]">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ route('tenants.update', $tenant) }}">
            @csrf
            @method('PATCH')

            <label for="name" class="block mb-1 text-sm">テナント名</label>

            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $tenant->name) }}"
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
            >

            <label class="flex items-center gap-2 mb-4 text-sm">
                <input
                    type="checkbox"
                    name="member_invites_enabled"
                    value="1"
                    @checked(old('member_invites_enabled', $tenant->member_invites_enabled))
                >
                メンバーが他の人を招待できるようにする
            </label>

            <button
                type="submit"
                class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
            >
                保存
            </button>
        </form>
    </div>
@endsection

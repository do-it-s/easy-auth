@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">プロフィールを入力</h1>

        @error('name')
            <p class="mb-4 text-[#F53003]">{{ $message }}</p>
        @enderror

        @php $leaveTenantHasError = $errors->getBag('leaveTenant')->has('name'); @endphp

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')

            <label for="name" class="block mb-1 text-sm">お名前</label>

            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $user->name) }}"
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
            >

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
                >
                    保存
                </button>

                @if ($currentTenant)
                    <button
                        type="button"
                        id="show-leave-tenant"
                        class="{{ $leaveTenantHasError ? 'hidden' : '' }} px-5 py-1.5 border border-[#19140035] rounded-sm text-sm leading-normal hover:bg-[#19140012]"
                    >
                        脱退
                    </button>
                @endif
            </div>
        </form>

        @if ($currentTenant)
            <div
                id="leave-tenant-section"
                class="{{ $leaveTenantHasError ? '' : 'hidden' }} mt-8 pt-6 border-t border-[#19140035]"
            >
                <form
                    method="POST"
                    action="{{ route('tenants.members.leave', $currentTenant) }}"
                >
                    @csrf
                    @method('DELETE')

                    <label for="leave-tenant-name" class="block mb-1 text-sm">
                        {{ $currentTenant->name }}を脱退するには名前を入力してください
                    </label>

                    <input
                        id="leave-tenant-name"
                        name="name"
                        type="text"
                        class="w-full mb-2 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
                    >

                    @error('name', 'leaveTenant')
                        <p class="mb-2 text-sm text-[#F53003]">{{ $message }}</p>
                    @enderror

                    <button
                        type="submit"
                        class="inline-block px-5 py-1.5 border border-[#19140035] rounded-sm text-sm leading-normal hover:bg-[#19140012]"
                    >
                        脱退
                    </button>
                </form>
            </div>
        @endif
    </div>
@endsection

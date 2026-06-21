@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">{{ __('easy-auth::tenants.delete_heading', ['tenant' => $tenant->name]) }}</h1>

        <p class="mb-4 text-sm">{{ __('easy-auth::tenants.delete_description') }}</p>

        <form method="POST" action="{{ route('tenants.delete', $tenant) }}">
            @csrf
            @method('DELETE')

            <label for="tenant_name" class="block mb-1 text-sm">
                {{ __('easy-auth::tenants.delete_confirm_label', ['tenant' => $tenant->name]) }}
            </label>

            <input
                id="tenant_name"
                name="tenant_name"
                type="text"
                class="w-full mb-2 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
            >

            @error('tenant_name')
                <p class="mb-2 text-sm text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
            @enderror

            <button
                type="submit"
                class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm leading-normal hover:bg-[#19140012] dark:hover:bg-[#eeeeec]/10"
            >
                {{ __('easy-auth::tenants.delete') }}
            </button>
        </form>
    </div>
@endsection

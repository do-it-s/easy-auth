@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">{{ __('easy-auth::tenants.create_heading') }}</h1>

        @error('name')
            <p class="mb-4 text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ route('tenants.store') }}">
            @csrf

            <label for="name" class="block mb-1 text-sm">{{ __('easy-auth::tenants.name') }}</label>

            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
            >

            <button
                type="submit"
                class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
            >
                {{ __('easy-auth::tenants.add') }}
            </button>
        </form>
    </div>
@endsection

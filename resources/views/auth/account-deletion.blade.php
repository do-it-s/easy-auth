@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">{{ __('easy-auth::account_deletion.heading') }}</h1>

        <p class="mb-4 text-sm">{{ __('easy-auth::account_deletion.description') }}</p>

        <form
            method="POST"
            action="{{ route('account-deletion.destroy', ['id' => $user->id, 'signature' => $signature, 'expires' => $expires]) }}"
        >
            @csrf
            @method('DELETE')

            <label for="name" class="block mb-1 text-sm">
                {{ __('easy-auth::account_deletion.confirm_label', ['name' => $user->name]) }}
            </label>

            <input
                id="name"
                name="name"
                type="text"
                class="w-full mb-2 px-2 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm"
            >

            @error('name')
                <p class="mb-2 text-sm text-[#F53003] dark:text-[#FF4433]">{{ $message }}</p>
            @enderror

            <button
                type="submit"
                class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
            >
                {{ __('easy-auth::account_deletion.delete_button') }}
            </button>
        </form>
    </div>
@endsection

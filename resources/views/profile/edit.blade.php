<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] text-[#1b1b18] flex p-6 items-center justify-center min-h-screen flex-col">
        <div class="w-full max-w-sm">
            <h1 class="mb-4 text-lg">プロフィールを入力</h1>

            @error('name')
                <p class="mb-4 text-[#F53003]">{{ $message }}</p>
            @enderror

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

                <button
                    type="submit"
                    class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
                >
                    保存
                </button>
            </form>
        </div>
    </body>
</html>

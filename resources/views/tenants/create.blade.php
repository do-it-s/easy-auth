<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] text-[#1b1b18] flex p-6 items-center justify-center min-h-screen flex-col">
        <div class="w-full max-w-sm">
            <h1 class="mb-4 text-lg">テナントを作成</h1>

            @error('name')
                <p class="mb-4 text-[#F53003]">{{ $message }}</p>
            @enderror

            <form method="POST" action="{{ route('tenants.store') }}">
                @csrf

                <label for="name" class="block mb-1 text-sm">テナント名</label>

                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
                >

                <button
                    type="submit"
                    class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
                >
                    作成
                </button>

                <a
                    href="{{ route('home') }}"
                    class="inline-block mt-4 px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
                >
                    ホームへ戻る
                </a>
            </form>
        </div>
    </body>
</html>

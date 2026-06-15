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
            <h1 class="mb-4 text-lg">メールアドレスでログイン</h1>

            @if ($errors->any())
                <div class="mb-4 text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <label for="email" class="block mb-1 text-sm">メールアドレス</label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
                >

                <label for="password" class="block mb-1 text-sm">パスワード</label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
                >

                <button
                    type="submit"
                    class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
                >
                    ログイン
                </button>
            </form>
        </div>
    </body>
</html>

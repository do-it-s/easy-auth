@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">メールアドレスでログイン</h1>

        <p id="login-status" class="mb-4 text-sm text-red-600"></p>

        <form id="login-form">
            <label for="email" class="block mb-1 text-sm">メールアドレス</label>

            <input
                id="email"
                name="email"
                type="email"
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
@endsection

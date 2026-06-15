@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">プロフィールを登録</h1>

        <form id="profile-create-form">
            <label for="name" class="block mb-1 text-sm">お名前</label>

            <input
                id="name"
                name="name"
                type="text"
                required
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
            >

            <button
                type="submit"
                class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
            >
                パスキーを登録して始める
            </button>
        </form>

        <button
            id="show-password-register"
            type="button"
            class="hidden mt-4 text-sm underline"
        >
            パスキーが使えない場合はこちら
        </button>

        <form id="password-register-form" class="hidden mt-4">
            <label for="register-name" class="block mb-1 text-sm">お名前</label>

            <input
                id="register-name"
                name="name"
                type="text"
                required
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
            >

            <label for="register-email" class="block mb-1 text-sm">メールアドレス</label>

            <input
                id="register-email"
                name="email"
                type="email"
                required
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
            >

            <label for="register-password" class="block mb-1 text-sm">パスワード</label>

            <input
                id="register-password"
                name="password"
                type="password"
                required
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
            >

            <label for="register-password-confirmation" class="block mb-1 text-sm">パスワード（確認）</label>

            <input
                id="register-password-confirmation"
                name="password_confirmation"
                type="password"
                required
                class="w-full mb-4 px-2 py-1.5 border border-[#19140035] rounded-sm text-sm"
            >

            <button
                type="submit"
                class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
            >
                メールアドレスで登録する
            </button>
        </form>
    </div>
@endsection

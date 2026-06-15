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
        <div class="w-full max-w-sm text-center">
            @if ($status === 'invalid')
                <p class="mb-4">この招待は無効です（期限切れまたは使用済み）。</p>
            @elseif ($status === 'already_member')
                <p class="mb-4">すでに「{{ $invitation->tenant->name }}」のメンバーです。</p>
            @elseif ($status === 'already_admin')
                <p class="mb-4">すでに「{{ $invitation->tenant->name }}」の管理者です。</p>
            @elseif ($alreadyMember)
                <p class="mb-4">
                    「{{ $invitation->tenant->name }}」の管理者に昇格しますか？
                </p>

                <form method="POST" action="{{ route('invitations.redeem', $token) }}">
                    @csrf

                    <button
                        type="submit"
                        class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
                    >
                        昇格する
                    </button>
                </form>
            @else
                <p class="mb-4">
                    「{{ $invitation->tenant->name }}」に
                    @if ($invitation->role === \App\Models\Tenant::ADMIN_ROLE)
                        管理者
                    @elseif ($invitation->role === \App\Models\Tenant::MEMBER_ROLE)
                        メンバー
                    @else
                        {{ $invitation->role }}
                    @endif
                    として参加しますか？
                </p>

                <form method="POST" action="{{ route('invitations.redeem', $token) }}">
                    @csrf

                    <button
                        type="submit"
                        class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
                    >
                        参加する
                    </button>
                </form>
            @endif

            <a
                href="{{ route('home') }}"
                class="inline-block mt-4 px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
            >
                ホームへ戻る
            </a>
        </div>
    </body>
</html>

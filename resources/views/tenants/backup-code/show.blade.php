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
            <h1 class="mb-4 text-lg">{{ $tenant->name }} の管理者バックアップコード</h1>

            <p class="mb-4 text-sm">
                管理者全員がデバイスを失った場合に備えて、復旧用のバックアップコードを発行できます。
                発行されたコードは管理者として1回だけ参加できる招待リンクです。安全な場所に保管してください。
            </p>

            @if ($invitationUrl)
                <div class="mb-4 p-3 border border-[#19140035] rounded-sm text-sm">
                    <p class="mb-2">バックアップコードを発行しました。このURLまたはQRコードを安全な場所に保管してください（このページを離れると再表示できません）。</p>
                    <p class="mb-2 break-all">{{ $invitationUrl }}</p>
                    <img src="{{ $invitationQrCode }}" alt="バックアップコードQRコード" class="w-40 h-40">
                </div>
            @else
                <p class="mb-4 text-sm">
                    現在のバックアップコード:
                    @if ($hasUsableBackupCode)
                        設定済み
                    @else
                        <span class="text-[#F53003]">未設定</span>
                    @endif
                </p>
            @endif

            <form method="POST" action="{{ route('tenants.backup-code.store', $tenant) }}">
                @csrf

                <button
                    type="submit"
                    class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
                >
                    {{ $hasUsableBackupCode ? 'バックアップコードを再発行' : 'バックアップコードを発行' }}
                </button>
            </form>

            @if ($hasUsableBackupCode)
                <p class="mt-2 text-sm">
                    再発行すると、既存のバックアップコードは無効になります。
                </p>
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

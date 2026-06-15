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
        <div class="w-full max-w-md">
            <h1 class="mb-4 text-lg">{{ $tenant->name }} の招待一覧</h1>

            <a
                href="{{ route('tenants.invitations.create', $tenant) }}"
                class="inline-block mb-4 px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
            >
                新しい招待を発行
            </a>

            @forelse ($invitations as $invitation)
                <div class="mb-2 p-3 border border-[#19140035] rounded-sm text-sm">
                    <p>
                        ロール:
                        @if ($invitation->role === \App\Models\Tenant::ADMIN_ROLE)
                            管理者
                        @elseif ($invitation->role === \App\Models\Tenant::MEMBER_ROLE)
                            メンバー
                        @else
                            {{ $invitation->role }}
                        @endif
                    </p>

                    @if ($invitation->label)
                        <p>メモ: {{ $invitation->label }}</p>
                    @endif

                    <p>
                        有効期限:
                        {{ $invitation->expires_at?->format('Y-m-d H:i') ?? '無期限' }}
                    </p>

                    <p>
                        状態:
                        @if ($invitation->isUsed())
                            使用済み
                        @elseif ($invitation->isExpired())
                            期限切れ
                        @else
                            有効
                        @endif
                    </p>

                    @can('delete', $invitation)
                        @if ($invitation->isUsable())
                            <form method="POST" action="{{ route('tenants.invitations.destroy', [$tenant, $invitation]) }}" class="mt-2">
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
                                >
                                    無効化
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            @empty
                <p class="text-sm">招待はまだありません。</p>
            @endforelse
        </div>
    </body>
</html>

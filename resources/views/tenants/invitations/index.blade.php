@extends('layouts.app')

@section('content')
    <div class="w-full max-w-md">
        <h1 class="mb-4 text-lg">{{ $tenant->name }} の招待一覧</h1>

        @forelse ($invitations as $invitation)
            <div class="mb-2 p-3 border border-[#19140035] rounded-sm text-sm">
                <p>
                    ロール:
                    @if ($invitation->role === \DoITs\EasyAuth\Models\Tenant::ADMIN_ROLE)
                        管理者
                    @elseif ($invitation->role === \DoITs\EasyAuth\Models\Tenant::MEMBER_ROLE)
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
@endsection

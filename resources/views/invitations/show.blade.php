@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm text-center">
        @if ($status === 'invalid')
            <p class="mb-4">この招待は無効です（期限切れまたは使用済み）。</p>
        @elseif ($status === 'already_admin')
            <p class="mb-4">すでに「{{ $invitation->tenant->name }}」の管理者です。</p>
        @elseif ($alreadyMember)
            <p class="mb-4">
                @if ($isPromotion)
                    「{{ $invitation->tenant->name }}」の管理者に昇格しますか？
                @else
                    「{{ $invitation->tenant->name }}」での参加情報を更新しますか？
                @endif
            </p>

            <form method="POST" action="{{ route('invitations.redeem', $token) }}">
                @csrf

                <button
                    type="submit"
                    class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
                >
                    {{ $isPromotion ? '昇格する' : '更新する' }}
                </button>
            </form>
        @else
            <p class="mb-4">
                「{{ $invitation->tenant->name }}」に
                @if ($invitation->role === \DoITs\EasyAuth\Models\Tenant::ADMIN_ROLE)
                    管理者
                @elseif ($invitation->role === \DoITs\EasyAuth\Models\Tenant::MEMBER_ROLE)
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
    </div>
@endsection

@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">{{ __('easy-auth::backup_code.heading', ['tenant' => $tenant->name]) }}</h1>

        <p class="mb-4 text-sm">
            {{ __('easy-auth::backup_code.intro') }}
        </p>

        @if ($invitationUrl)
            <div class="mb-4 p-3 border border-[#19140035] rounded-sm text-sm">
                <p class="mb-2">{{ __('easy-auth::backup_code.issued_notice') }}</p>
                <p class="mb-2 break-all">{{ $invitationUrl }}</p>
                <img src="{{ $invitationQrCode }}" alt="{{ __('easy-auth::backup_code.qr_alt') }}" class="w-40 h-40">
            </div>
        @else
            <p class="mb-4 text-sm">
                {{ __('easy-auth::backup_code.current_label') }}
                @if ($hasUsableBackupCode)
                    {{ __('easy-auth::backup_code.configured') }}
                @else
                    <span class="text-[#F53003]">{{ __('easy-auth::backup_code.not_configured') }}</span>
                @endif
            </p>
        @endif

        <form method="POST" action="{{ route('tenants.backup-code.store', $tenant) }}">
            @csrf

            <button
                type="submit"
                class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#19140035] rounded-sm text-sm leading-normal"
            >
                {{ $hasUsableBackupCode ? __('easy-auth::backup_code.reissue_button') : __('easy-auth::backup_code.issue_button') }}
            </button>
        </form>

        @if ($hasUsableBackupCode)
            <p class="mt-2 text-sm">
                {{ __('easy-auth::backup_code.reissue_notice') }}
            </p>
        @endif
    </div>
@endsection

@props(['tenant', 'invitationUrl', 'invitationQrCode', 'hasUsableBackupCode'])

<div class="w-full max-w-sm">
    <h1 class="mb-4 text-lg">{{ __('easy-auth::backup_code.heading', ['tenant' => $tenant->name]) }}</h1>

    <p class="mb-4 text-sm">
        {{ __('easy-auth::backup_code.intro') }}
    </p>

    @if ($invitationUrl)
        <div class="mb-4 p-3 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm text-sm">
            <p class="mb-2">{{ __('easy-auth::backup_code.issued_notice') }}</p>
            <div class="mb-2 flex items-center gap-2">
                <p class="break-all flex-1">{{ $invitationUrl }}</p>
                <x-easy-auth::shared.copy-to-clipboard-button
                    :url="$invitationUrl"
                    :label="__('easy-auth::backup_code.copy_button')"
                    :label-copied="__('easy-auth::backup_code.copy_done')"
                />
            </div>
            <img src="{{ $invitationQrCode }}" alt="{{ __('easy-auth::backup_code.qr_alt') }}" class="w-40 h-40">
        </div>
    @else
        <p class="mb-4 text-sm">
            {{ __('easy-auth::backup_code.current_label') }}
            @if ($hasUsableBackupCode)
                {{ __('easy-auth::backup_code.configured') }}
            @else
                <span class="text-[#F53003] dark:text-[#FF4433]">{{ __('easy-auth::backup_code.not_configured') }}</span>
            @endif
        </p>
    @endif

    <form method="POST" action="{{ route('tenants.backup-code.store', $tenant) }}">
        @csrf

        <button
            type="submit"
            class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
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

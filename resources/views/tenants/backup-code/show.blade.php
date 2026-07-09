@extends('layouts.app')

@section('content')
    <x-easy-auth::tenants.backup-code-panel
        :tenant="$tenant"
        :invitation-url="$invitationUrl"
        :invitation-qr-code="$invitationQrCode"
        :has-usable-backup-code="$hasUsableBackupCode"
    />
@endsection

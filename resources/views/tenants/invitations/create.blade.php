@extends('layouts.app')

@section('content')
    <x-easy-auth::invitations.create-form
        :tenant="$tenant"
        :invitation-url="$invitationUrl"
        :invitation-qr-code="$invitationQrCode"
        :is-admin="$isAdmin"
        :default-expires-at="$defaultExpiresAt"
    />
@endsection

@extends('layouts.app')

@section('content')
    <x-easy-auth::invitations.redeem-panel
        :status="$status"
        :invitation="$invitation"
        :token="$token ?? null"
        :already-member="$alreadyMember ?? false"
        :is-promotion="$isPromotion ?? false"
    />
@endsection

@extends('layouts.app')

@section('content')
    <x-easy-auth::invitations.list :tenant="$tenant" :invitations="$invitations" />
@endsection

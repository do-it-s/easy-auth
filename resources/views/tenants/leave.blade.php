@extends('layouts.app')

@section('content')
    <x-easy-auth::tenants.leave-confirm-form :tenant="$tenant" />
@endsection

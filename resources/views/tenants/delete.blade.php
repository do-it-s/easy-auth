@extends('layouts.app')

@section('content')
    <x-easy-auth::tenants.delete-confirm-form :tenant="$tenant" />
@endsection

@extends('layouts.app')

@section('content')
    <x-easy-auth::tenants.edit-form :tenant="$tenant" />
@endsection

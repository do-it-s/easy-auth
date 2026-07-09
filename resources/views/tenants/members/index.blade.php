@extends('layouts.app')

@section('content')
    <x-easy-auth::tenants.member-list
        :tenant="$tenant"
        :admins="$admins"
        :others="$others"
        :admin-count="$adminCount"
    />
@endsection

@extends('layouts.app')

@section('content')
    <x-easy-auth::auth.account-deletion-confirm-form :user="$user" :signature="$signature" :expires="$expires" />
@endsection

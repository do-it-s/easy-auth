@extends('layouts.app')

@section('content')
    <x-easy-auth::auth.password-reset-form :token="$token" :email="$email" />
@endsection

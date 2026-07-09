@extends('layouts.app')

@section('content')
    <x-easy-auth::profile.delete-confirm-form :user="$user" />
@endsection

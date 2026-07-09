@extends('layouts.app')

@section('content')
    <x-easy-auth::profile.edit-form :user="$user" />
@endsection

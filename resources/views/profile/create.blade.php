@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">{{ __('easy-auth::profile.create_heading') }}</h1>

        <x-easy-auth::profile.already-registered-notice />

        <div id="registration-forms">
            <x-easy-auth::profile.passkey-registration-form />
            <x-easy-auth::profile.password-registration-form />
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('content')
    <div class="w-full max-w-sm">
        <h1 class="mb-4 text-lg">{{ __('easy-auth::account_deletion.deleted_heading') }}</h1>

        <p class="text-sm">{{ __('easy-auth::account_deletion.deleted') }}</p>
    </div>
@endsection

@push('scripts')
    <script>
        localStorage.removeItem('device_uuid');
        localStorage.removeItem('auth_method');
    </script>
@endpush

<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasskeyRegistrationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::middleware(array_filter(['guest', config('passkeys.throttle')]))->group(function () {
    Route::get('/register/passkey/options', [PasskeyRegistrationController::class, 'create']);
    Route::post('/register/passkey', [PasskeyRegistrationController::class, 'store']);
});

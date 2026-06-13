<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::middleware(array_filter(['guest', config('passkeys.throttle')]))->group(function () {
    Route::get('/profile/create', [ProfileController::class, 'create'])->name('profile.create');
    Route::get('/profile/passkey-options', [ProfileController::class, 'passkeyOptions'])->name('profile.passkey-options');
    Route::post('/profile', [ProfileController::class, 'store'])->name('profile.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware('profile.complete')->group(function () {
    Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
});

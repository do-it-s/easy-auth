<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BackupCodeController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\InvitationRedemptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantMemberController;
use App\Http\Controllers\TenantSwitchController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::view('/device/reset', 'device.reset')->name('device.reset');

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::middleware(array_filter(['guest', config('passkeys.throttle')]))->group(function () {
    Route::get('/profile/create', [ProfileController::class, 'create'])->name('profile.create');
    Route::get('/profile/passkey-options', [ProfileController::class, 'passkeyOptions'])->name('profile.passkey-options');
    Route::post('/profile', [ProfileController::class, 'store'])->name('profile.store');
    Route::post('/profile-password', [RegisterController::class, 'store'])->name('register');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware('profile.complete')->group(function () {
    Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
    Route::patch('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::post('/tenants/{tenant}/switch', [TenantSwitchController::class, 'switch'])->name('tenants.switch');

    Route::get('/tenants/{tenant}/backup-code', [BackupCodeController::class, 'show'])->name('tenants.backup-code.show');
    Route::post('/tenants/{tenant}/backup-code', [BackupCodeController::class, 'store'])->name('tenants.backup-code.store');

    Route::get('/tenants/{tenant}/members', [TenantMemberController::class, 'index'])->name('tenants.members.index');
    Route::patch('/tenants/{tenant}/members/{user}', [TenantMemberController::class, 'update'])->name('tenants.members.update');
    Route::delete('/tenants/{tenant}/members/{user}', [TenantMemberController::class, 'destroy'])->name('tenants.members.destroy');

    Route::get('/tenants/{tenant}/invitations', [InvitationController::class, 'index'])->name('tenants.invitations.index');
    Route::get('/tenants/{tenant}/invitations/create', [InvitationController::class, 'create'])->name('tenants.invitations.create');
    Route::post('/tenants/{tenant}/invitations', [InvitationController::class, 'store'])->name('tenants.invitations.store');
    Route::delete('/tenants/{tenant}/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('tenants.invitations.destroy');
});

Route::get('/invitations/{token}', [InvitationRedemptionController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}', [InvitationRedemptionController::class, 'redeem'])->middleware('auth')->name('invitations.redeem');

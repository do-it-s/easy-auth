<?php

use DoITs\EasyAuth\Http\Controllers\Auth\AccountDeletionController;
use DoITs\EasyAuth\Http\Controllers\Auth\LoginController;
use DoITs\EasyAuth\Http\Controllers\Auth\LogoutController;
use DoITs\EasyAuth\Http\Controllers\Auth\PasswordResetController;
use DoITs\EasyAuth\Http\Controllers\Auth\RegisterController;
use DoITs\EasyAuth\Http\Controllers\BackupCodeController;
use DoITs\EasyAuth\Http\Controllers\InvitationController;
use DoITs\EasyAuth\Http\Controllers\InvitationRedemptionController;
use DoITs\EasyAuth\Http\Controllers\ProfileController;
use DoITs\EasyAuth\Http\Controllers\TenantController;
use DoITs\EasyAuth\Http\Controllers\TenantLeaveController;
use DoITs\EasyAuth\Http\Controllers\TenantMemberController;
use DoITs\EasyAuth\Http\Controllers\TenantSwitchController;
use Illuminate\Support\Facades\Route;

Route::view('/device/reset', 'easy-auth::device.reset')->name('device.reset');
Route::view('/account-deletion/deleted', 'easy-auth::auth.account-deleted')->name('account-deletion.deleted');

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::middleware(array_filter(['guest', config('passkeys.throttle')]))->group(function () {
    Route::get('/profile/create', [ProfileController::class, 'create'])->name('profile.create');
    Route::get('/profile/passkey-options', [ProfileController::class, 'passkeyOptions'])->name('profile.passkey-options');
    Route::post('/profile', [ProfileController::class, 'store'])->name('profile.store');
    Route::post('/profile-password', [RegisterController::class, 'store'])->name('register');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/account-deletion/{id}', [AccountDeletionController::class, 'show'])->name('account-deletion.show')->middleware('signed');
    Route::delete('/account-deletion/{id}', [AccountDeletionController::class, 'destroy'])->name('account-deletion.destroy')->middleware('signed');

    Route::get('/password/request', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/password/email', [PasswordResetController::class, 'store'])->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/delete', [ProfileController::class, 'show'])->name('profile.delete.show');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.delete');
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
    Route::get('/tenants/{tenant}/leave', [TenantLeaveController::class, 'show'])->name('tenants.leave.show');
    Route::delete('/tenants/{tenant}/leave', [TenantLeaveController::class, 'destroy'])->name('tenants.leave');

    Route::get('/tenants/{tenant}/invitations', [InvitationController::class, 'index'])->name('tenants.invitations.index');
    Route::get('/tenants/{tenant}/invitations/create', [InvitationController::class, 'create'])->name('tenants.invitations.create');
    Route::post('/tenants/{tenant}/invitations', [InvitationController::class, 'store'])->name('tenants.invitations.store');
    Route::delete('/tenants/{tenant}/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('tenants.invitations.destroy');
});

Route::get('/invitations/{token}', [InvitationRedemptionController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}', [InvitationRedemptionController::class, 'redeem'])->middleware('auth')->name('invitations.redeem');

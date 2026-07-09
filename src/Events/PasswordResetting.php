<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by PasswordResetController::update() immediately before the
 * new password is forceFill()'d and saved. Named to avoid colliding with
 * Illuminate\Auth\Events\PasswordReset, which this package's controller
 * never triggers: that event is fired by the old Illuminate\Foundation\Auth\
 * ResetsPasswords controller trait, not by the Password facade's
 * PasswordBroker::reset() itself, and this package does not use that trait.
 */
class PasswordResetting
{
    use Dispatchable;

    public function __construct(
        public readonly EasyAuthUser $user,
        public readonly string $password,
    ) {}
}

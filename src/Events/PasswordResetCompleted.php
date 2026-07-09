<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by PasswordResetController::update() immediately after the new
 * password is saved. See PasswordResetting for why this is not named
 * PasswordReset.
 */
class PasswordResetCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly EasyAuthUser $user,
    ) {}
}

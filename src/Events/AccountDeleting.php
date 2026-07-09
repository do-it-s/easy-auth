<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by AccountDeletionController::destroy() immediately before
 * $user->delete(). Laravel has no built-in event for user deletion, unlike
 * Login/Failed/Logout which Auth::attempt() already fires on its own.
 */
class AccountDeleting
{
    use Dispatchable;

    public function __construct(
        public readonly EasyAuthUser $user,
    ) {}
}

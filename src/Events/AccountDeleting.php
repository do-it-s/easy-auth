<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched immediately before $user->delete(), by AccountDeletionController
 * ::destroy() (device-mismatch lockout flow) and ProfileController::destroy()
 * (self-service deletion from a signed-in session) alike — both represent
 * the same "a User is about to be deleted" event, just reached via different
 * routes. Laravel has no built-in event for user deletion, unlike
 * Login/Failed/Logout which Auth::attempt() already fires on its own.
 */
class AccountDeleting
{
    use Dispatchable;

    public function __construct(
        public readonly EasyAuthUser $user,
    ) {}
}

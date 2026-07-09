<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched immediately after $user->delete(), by AccountDeletionController
 * ::destroy() (device-mismatch lockout flow) and ProfileController::destroy()
 * (self-service deletion from a signed-in session) alike — both represent
 * the same "a User was deleted" event, just reached via different routes.
 * The model instance is still usable for reading its (now-deleted)
 * attributes, e.g. for an audit log entry.
 */
class AccountDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly EasyAuthUser $user,
    ) {}
}

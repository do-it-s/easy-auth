<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by AccountDeletionController::destroy() immediately after
 * $user->delete(). The model instance is still usable for reading its
 * (now-deleted) attributes, e.g. for an audit log entry.
 */
class AccountDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly EasyAuthUser $user,
    ) {}
}

<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by ProfileController::store() (passkey registration) and
 * RegisterController::store() (password fallback registration) immediately
 * after the user row is created. Both routes represent the same "a User was
 * newly registered" event; $authMethod distinguishes which path was taken
 * for listeners that care.
 */
class UserRegistered
{
    use Dispatchable;

    public function __construct(
        public readonly EasyAuthUser $user,
        public readonly string $authMethod,
    ) {}
}

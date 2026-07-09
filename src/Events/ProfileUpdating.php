<?php

namespace DoITs\EasyAuth\Events;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by ProfileController::update() immediately before the
 * validated attributes are saved, since that call only fills the
 * hardcoded 'name' field and an app adding its own profile fields needs a
 * point to persist them alongside it.
 */
class ProfileUpdating
{
    use Dispatchable;

    public function __construct(
        public readonly EasyAuthUser $user,
        public readonly array $validated,
    ) {}
}

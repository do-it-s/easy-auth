<?php

namespace DoITs\EasyAuth;

/**
 * Static escape hatch mirroring laravel/fortify's Fortify::ignoreRoutes().
 * Host apps that need routes this package can't express through its own
 * customization layers (different URIs, dropping a route entirely, a
 * different middleware group) call ignoreRoutes() from their own
 * AppServiceProvider::register() and then declare their own routes
 * pointing at this package's controllers directly.
 */
class EasyAuth
{
    public static bool $registersRoutes = true;

    public static function ignoreRoutes(): void
    {
        static::$registersRoutes = false;
    }
}

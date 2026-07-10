<?php

use DoITs\EasyAuth\EasyAuth;
use Illuminate\Support\Facades\Route;

afterEach(function () {
    EasyAuth::$registersRoutes = true;
});

test('the package registers its routes by default', function () {
    expect(Route::has('login'))->toBeTrue();
});

test('EasyAuth::ignoreRoutes() stops the package from registering its routes', function () {
    EasyAuth::ignoreRoutes();

    $this->refreshApplication();

    expect(Route::has('login'))->toBeFalse();
});

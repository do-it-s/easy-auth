<?php

use DoITs\EasyAuth\EasyAuthServiceProvider;
use Illuminate\Support\ServiceProvider;

test('the easy-auth-lang tag publishes the lang directory to lang/vendor/easy-auth', function () {
    $paths = ServiceProvider::pathsToPublish(EasyAuthServiceProvider::class, 'easy-auth-lang');

    expect($paths)->toHaveCount(1);

    [$from, $to] = [array_key_first($paths), array_shift($paths)];

    expect(realpath($from))->toBe(realpath(__DIR__.'/../../lang'))
        ->and($to)->toBe(lang_path('vendor/easy-auth'));
});

<?php

use DoITs\EasyAuth\Tests\Fixtures\User;

test('a user matching the configured sysadmin_user_id is a system administrator', function () {
    $user = User::factory()->create();

    config(['easy-auth.sysadmin_user_id' => $user->id]);

    expect($user->isSysAdmin())->toBeTrue();
});

test('a user not matching the configured sysadmin_user_id is not a system administrator', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    config(['easy-auth.sysadmin_user_id' => $other->id]);

    expect($user->isSysAdmin())->toBeFalse();
});

test('no user is a system administrator when sysadmin_user_id is unset', function () {
    $user = User::factory()->create();

    config(['easy-auth.sysadmin_user_id' => null]);

    expect($user->isSysAdmin())->toBeFalse();
});

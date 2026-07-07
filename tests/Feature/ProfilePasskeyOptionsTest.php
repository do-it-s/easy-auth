<?php

beforeEach(function () {
    config([
        'passkeys.relying_party_id' => 'example.test',
        'passkeys.user_handle_secret' => 'test-secret',
    ]);
});

test('passkey registration options use the name submitted from the profile creation form for both username and display name', function () {
    $response = $this->getJson('/profile/passkey-options?name='.urlencode('Taro'));

    $response->assertOk();

    $user = $response->json('options.user');

    // email is deliberately not consulted for either field — see
    // IsEasyAuthUser::getPasskeyDisplayName().
    expect($user['name'])->toBe('Taro');
    expect($user['displayName'])->toBe('Taro');
});

test('passkey registration options fall back to a placeholder when no name is submitted', function () {
    $response = $this->getJson('/profile/passkey-options');

    $response->assertOk();

    $user = $response->json('options.user');
    $placeholder = trans('easy-auth::profile.passkey_pending_identity');

    expect($user['name'])->toBe($placeholder);
    expect($user['displayName'])->toBe($placeholder);
});

test('passkey registration options fall back to a placeholder when the name is only whitespace', function () {
    $response = $this->getJson('/profile/passkey-options?name='.urlencode('   '));

    $response->assertOk();

    $user = $response->json('options.user');
    $placeholder = trans('easy-auth::profile.passkey_pending_identity');

    expect($user['name'])->toBe($placeholder);
    expect($user['displayName'])->toBe($placeholder);
});

test('passkey registration options do not restrict authenticatorAttachment by default', function () {
    $response = $this->getJson('/profile/passkey-options');

    $response->assertOk();

    expect($response->json('options.authenticatorSelection'))->not->toHaveKey('authenticatorAttachment');
});

test('passkey registration options force the platform authenticatorAttachment when configured', function () {
    config(['easy-auth.force_platform_authenticator' => true]);

    $response = $this->getJson('/profile/passkey-options');

    $response->assertOk();

    expect($response->json('options.authenticatorSelection.authenticatorAttachment'))->toBe('platform');
});

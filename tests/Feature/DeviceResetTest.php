<?php

test('the device reset page renders the reset-panel component with its expected elements', function () {
    $response = $this->get('/device/reset');

    $response->assertOk();
    $response->assertSee('id="device-uuid"', false);
    $response->assertSee('id="auth-method"', false);
    $response->assertSee('id="clear"', false);
    $response->assertSee('id="status"', false);
    $response->assertSee('data-easy-auth-strings', false);
});

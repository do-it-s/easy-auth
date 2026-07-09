<?php

use Illuminate\Support\Facades\Blade;

test('the copy-to-clipboard-button component can be rendered twice on the same page without duplicating its script', function () {
    $html = Blade::render(<<<'BLADE'
        <x-easy-auth::shared.copy-to-clipboard-button url="https://example.test/a" label="Copy" label-copied="Copied!" />
        <x-easy-auth::shared.copy-to-clipboard-button url="https://example.test/b" label="Copy" label-copied="Copied!" />
    BLADE);

    expect(substr_count($html, '<button'))->toBe(2);
    expect(substr_count($html, 'data-url="https://example.test/a"'))->toBe(1);
    expect(substr_count($html, 'data-url="https://example.test/b"'))->toBe(1);
    expect(substr_count($html, 'addEventListener'))->toBe(1);
});

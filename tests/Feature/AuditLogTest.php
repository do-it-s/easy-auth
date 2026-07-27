<?php

use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Tests\Fixtures\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Replaces the Log facade with a fake that records every entry written to
 * config('easy-auth.audit_log_channel'), so tests can assert on the
 * structured payload without touching the filesystem.
 *
 * @return array<int, array{action: string, context: array}>
 */
function &captureAuditLog(): array
{
    $entries = [];

    Log::shouldReceive('channel')
        ->with(config('easy-auth.audit_log_channel'))
        ->andReturnSelf();

    Log::shouldReceive('info')
        ->andReturnUsing(function (string $action, array $context) use (&$entries) {
            $entries[] = ['action' => $action, 'context' => $context];
        });

    return $entries;
}

test('the audit log channel is registered with a daily driver and the configured retention', function () {
    $channel = config('easy-auth.audit_log_channel');

    expect(config("logging.channels.{$channel}"))->toMatchArray([
        'driver' => 'daily',
        'days' => config('easy-auth.audit_log_retention_days'),
    ]);
});

test('removing a tenant member writes an audit log entry naming the actor and target', function () {
    $entries = &captureAuditLog();

    $admin = User::factory()->create(['name' => 'Admin']);
    $member = User::factory()->create(['name' => 'Taro']);
    $tenant = Tenant::factory()->create();
    $tenant->users()->attach($admin, ['role' => Tenant::ADMIN_ROLE, 'last_accessed_at' => now()]);
    $tenant->users()->attach($member, ['role' => Tenant::MEMBER_ROLE, 'last_accessed_at' => now()]);

    $this->actingAs($admin)->delete(route('tenants.members.destroy', [$tenant, $member]));

    $entry = collect($entries)->firstWhere('action', 'tenant_member.removed');

    expect($entry)->not->toBeNull();
    expect($entry['context']['outcome'])->toBe('success');
    expect($entry['context']['actor']['id'])->toBe($admin->id);
    expect($entry['context']['target']['id'])->toBe($member->id);
    expect($entry['context']['tenant']['id'])->toBe($tenant->id);
});

test('a device-mismatched sign-in attempt writes an audit log entry without leaking the attempted email', function () {
    Notification::fake();
    $entries = &captureAuditLog();

    $user = User::factory()->create(['email' => 'taro@example.com', 'password' => 'password']);
    $user->device()->create(['uuid' => (string) Str::uuid()]);

    $this->postJson('/login', [
        'email' => 'taro@example.com',
        'password' => 'password',
    ], ['X-Device-Uuid' => (string) Str::uuid()]);

    $entry = collect($entries)->firstWhere('action', 'auth.device_mismatch');

    expect($entry)->not->toBeNull();
    expect($entry['context']['outcome'])->toBe('failure');
    expect($entry['context']['actor']['id'])->toBe($user->id);
});

test('a failed password sign-in writes an audit log entry without the attempted email or resolved user', function () {
    $entries = &captureAuditLog();

    User::factory()->create(['email' => 'taro@example.com', 'password' => 'password']);

    $this->postJson('/login', [
        'email' => 'taro@example.com',
        'password' => 'wrong-password',
    ]);

    $entry = collect($entries)->firstWhere('action', 'auth.failed');

    expect($entry)->not->toBeNull();
    expect($entry['context']['outcome'])->toBe('failure');
    expect($entry['context'])->not->toHaveKey('actor');
    expect(json_encode($entry))->not->toContain('taro@example.com');
});

test('a successful password sign-in writes an audit log entry naming the user', function () {
    $entries = &captureAuditLog();

    $user = User::factory()->create(['email' => 'taro@example.com', 'password' => 'password']);

    $this->postJson('/login', [
        'email' => 'taro@example.com',
        'password' => 'password',
    ]);

    $entry = collect($entries)->firstWhere('action', 'auth.login');

    expect($entry)->not->toBeNull();
    expect($entry['context']['outcome'])->toBe('success');
    expect($entry['context']['actor']['id'])->toBe($user->id);
});

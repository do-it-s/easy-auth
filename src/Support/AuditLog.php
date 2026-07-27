<?php

namespace DoITs\EasyAuth\Support;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Writes one structured line per authentication attempt or tenant/invitation/
 * profile operation to the channel named by config('easy-auth.audit_log_channel').
 * The whole point of this log is to reconstruct "who did what, when, with
 * what result" after the fact from disk alone, so every entry is
 * self-contained (actor/target identifiers and names, not just foreign
 * keys) and never carries credentials or attempted-but-wrong identifiers
 * (see SignInController, which deliberately omits the submitted email on
 * failure).
 */
class AuditLog
{
    /**
     * Record one audit entry.
     *
     * @param  array<string, mixed>  $context  Event-specific fields (e.g. tenant, target, role).
     */
    public static function record(string $action, string $outcome, array $context = []): void
    {
        Log::channel(config('easy-auth.audit_log_channel'))->info($action, array_merge([
            'action' => $action,
            'outcome' => $outcome,
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'device_uuid' => Request::header('X-Device-Uuid'),
        ], $context));
    }

    /**
     * Describe a user as {id, name}, or null if there is none (e.g. a
     * sign-in attempt against an email that doesn't exist).
     *
     * @return array{id: int|string, name: string}|null
     */
    public static function describeUser(?EasyAuthUser $user): ?array
    {
        if (! $user) {
            return null;
        }

        return ['id' => $user->getAuthIdentifier(), 'name' => $user->getAttribute('name')];
    }

    /**
     * Describe a tenant as {id, name}.
     *
     * @return array{id: int|string, name: string}
     */
    public static function describeTenant(Tenant $tenant): array
    {
        return ['id' => $tenant->id, 'name' => $tenant->name];
    }
}

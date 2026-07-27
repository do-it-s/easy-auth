<?php

namespace DoITs\EasyAuth\Listeners;

use DoITs\EasyAuth\Events\AccountDeleted;
use DoITs\EasyAuth\Events\BackupCodeIssued;
use DoITs\EasyAuth\Events\InvitationCreated;
use DoITs\EasyAuth\Events\InvitationRedeemed;
use DoITs\EasyAuth\Events\InvitationRevoked;
use DoITs\EasyAuth\Events\PasswordResetCompleted;
use DoITs\EasyAuth\Events\ProfileUpdated;
use DoITs\EasyAuth\Events\TenantCreated;
use DoITs\EasyAuth\Events\TenantDeleted;
use DoITs\EasyAuth\Events\TenantMemberRemoved;
use DoITs\EasyAuth\Events\TenantMemberRoleUpdated;
use DoITs\EasyAuth\Events\TenantUpdated;
use DoITs\EasyAuth\Events\UserRegistered;
use DoITs\EasyAuth\Support\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use Laravel\Passkeys\Events\PasskeyDeleted;
use Laravel\Passkeys\Events\PasskeyRegistered;
use Laravel\Passkeys\Events\PasskeyVerified;

/**
 * Writes one AuditLog::record() entry per package event and per framework
 * authentication event (Login/Failed/Logout — fired by Auth::attempt() and
 * the passkey guard alike, so this single subscription covers both sign-in
 * methods). Only the "...ed" (already-happened) half of each Creating/
 * Created-style event pair is subscribed to, since the audit log records
 * completed outcomes, not in-flight attempts.
 *
 * Failed deliberately omits both the attempted identifier and the resolved
 * user, if any: SignInController already avoids confirming whether an email
 * belongs to an account for a device-mismatched attempt, and doing the
 * opposite here — writing a definitive answer to disk — would undermine
 * that.
 */
class AuditLogSubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            TenantCreated::class => 'onTenantCreated',
            TenantUpdated::class => 'onTenantUpdated',
            TenantDeleted::class => 'onTenantDeleted',
            TenantMemberRemoved::class => 'onTenantMemberRemoved',
            TenantMemberRoleUpdated::class => 'onTenantMemberRoleUpdated',
            InvitationCreated::class => 'onInvitationCreated',
            InvitationRevoked::class => 'onInvitationRevoked',
            InvitationRedeemed::class => 'onInvitationRedeemed',
            BackupCodeIssued::class => 'onBackupCodeIssued',
            ProfileUpdated::class => 'onProfileUpdated',
            PasswordResetCompleted::class => 'onPasswordResetCompleted',
            AccountDeleted::class => 'onAccountDeleted',
            UserRegistered::class => 'onUserRegistered',
            Login::class => 'onLogin',
            Failed::class => 'onFailed',
            Logout::class => 'onLogout',
            PasskeyRegistered::class => 'onPasskeyRegistered',
            PasskeyVerified::class => 'onPasskeyVerified',
            PasskeyDeleted::class => 'onPasskeyDeleted',
        ];
    }

    public function onTenantCreated(TenantCreated $event): void
    {
        AuditLog::record('tenant.created', 'success', [
            'actor' => AuditLog::describeUser($event->user),
            'tenant' => AuditLog::describeTenant($event->tenant),
        ]);
    }

    public function onTenantUpdated(TenantUpdated $event): void
    {
        AuditLog::record('tenant.updated', 'success', [
            'actor' => AuditLog::describeUser(auth()->user()),
            'tenant' => AuditLog::describeTenant($event->tenant),
        ]);
    }

    public function onTenantDeleted(TenantDeleted $event): void
    {
        AuditLog::record('tenant.deleted', 'success', [
            'actor' => AuditLog::describeUser(auth()->user()),
            'tenant' => AuditLog::describeTenant($event->tenant),
        ]);
    }

    public function onTenantMemberRemoved(TenantMemberRemoved $event): void
    {
        AuditLog::record('tenant_member.removed', 'success', [
            'actor' => AuditLog::describeUser(auth()->user()),
            'tenant' => AuditLog::describeTenant($event->tenant),
            'target' => AuditLog::describeUser($event->member),
        ]);
    }

    public function onTenantMemberRoleUpdated(TenantMemberRoleUpdated $event): void
    {
        AuditLog::record('tenant_member.role_updated', 'success', [
            'actor' => AuditLog::describeUser(auth()->user()),
            'tenant' => AuditLog::describeTenant($event->tenant),
            'target' => AuditLog::describeUser($event->member),
            'role' => $event->role,
        ]);
    }

    public function onInvitationCreated(InvitationCreated $event): void
    {
        AuditLog::record('invitation.created', 'success', [
            'actor' => AuditLog::describeUser(auth()->user()),
            'tenant' => AuditLog::describeTenant($event->invitation->tenant),
            'invitation_id' => $event->invitation->id,
            'role' => $event->invitation->role,
            'max_uses' => $event->invitation->max_uses,
            'expires_at' => $event->invitation->expires_at?->toIso8601String(),
        ]);
    }

    public function onInvitationRevoked(InvitationRevoked $event): void
    {
        AuditLog::record('invitation.revoked', 'success', [
            'actor' => AuditLog::describeUser(auth()->user()),
            'tenant' => AuditLog::describeTenant($event->invitation->tenant),
            'invitation_id' => $event->invitation->id,
        ]);
    }

    public function onInvitationRedeemed(InvitationRedeemed $event): void
    {
        AuditLog::record('invitation.redeemed', 'success', [
            'actor' => AuditLog::describeUser($event->user),
            'tenant' => AuditLog::describeTenant($event->invitation->tenant),
            'invitation_id' => $event->invitation->id,
            'role' => $event->invitation->role,
            'is_backup_code' => $event->invitation->is_backup_code,
        ]);
    }

    public function onBackupCodeIssued(BackupCodeIssued $event): void
    {
        AuditLog::record('backup_code.issued', 'success', [
            'actor' => AuditLog::describeUser(auth()->user()),
            'tenant' => AuditLog::describeTenant($event->tenant),
            'invitation_id' => $event->invitation->id,
        ]);
    }

    public function onProfileUpdated(ProfileUpdated $event): void
    {
        AuditLog::record('profile.updated', 'success', [
            'actor' => AuditLog::describeUser($event->user),
        ]);
    }

    public function onPasswordResetCompleted(PasswordResetCompleted $event): void
    {
        AuditLog::record('password.reset_completed', 'success', [
            'actor' => AuditLog::describeUser($event->user),
        ]);
    }

    public function onAccountDeleted(AccountDeleted $event): void
    {
        AuditLog::record('account.deleted', 'success', [
            'actor' => AuditLog::describeUser($event->user),
        ]);
    }

    public function onUserRegistered(UserRegistered $event): void
    {
        AuditLog::record('user.registered', 'success', [
            'actor' => AuditLog::describeUser($event->user),
            'auth_method' => $event->authMethod,
        ]);
    }

    public function onLogin(Login $event): void
    {
        AuditLog::record('auth.login', 'success', [
            'actor' => AuditLog::describeUser($event->user),
            'guard' => $event->guard,
        ]);
    }

    public function onFailed(Failed $event): void
    {
        AuditLog::record('auth.failed', 'failure', [
            'guard' => $event->guard,
        ]);
    }

    public function onLogout(Logout $event): void
    {
        AuditLog::record('auth.logout', 'success', [
            'actor' => AuditLog::describeUser($event->user),
            'guard' => $event->guard,
        ]);
    }

    public function onPasskeyRegistered(PasskeyRegistered $event): void
    {
        AuditLog::record('passkey.registered', 'success', [
            'actor' => AuditLog::describeUser($event->user),
            'passkey_id' => $event->passkey->id,
        ]);
    }

    public function onPasskeyVerified(PasskeyVerified $event): void
    {
        AuditLog::record('passkey.verified', 'success', [
            'actor' => AuditLog::describeUser($event->user),
            'passkey_id' => $event->passkey->id,
        ]);
    }

    public function onPasskeyDeleted(PasskeyDeleted $event): void
    {
        AuditLog::record('passkey.deleted', 'success', [
            'actor' => AuditLog::describeUser($event->user),
            'passkey_id' => $event->passkey->id,
        ]);
    }
}

<?php

namespace DoITs\EasyAuth\Tests\Fixtures;

use DoITs\EasyAuth\Concerns\IsEasyAuthUser;
use DoITs\EasyAuth\Contracts\EasyAuthUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

/**
 * Minimal stand-in for a host application's own User model, used only by
 * this package's own test suite to exercise Contracts\EasyAuthUser.
 */
class User extends Authenticatable implements EasyAuthUser, PasskeyUser
{
    use HasFactory, IsEasyAuthUser, Notifiable, PasskeyAuthenticatable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}

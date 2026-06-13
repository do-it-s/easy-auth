<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['uuid'])]
class Device extends Model
{
    /**
     * The user this device belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Find the user registered with the given device UUID.
     */
    public static function findUserByUuid(string $uuid): ?User
    {
        return static::where('uuid', $uuid)->first()?->user;
    }
}

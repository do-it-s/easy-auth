<?php

namespace DoITs\EasyAuth\Models;

use DoITs\EasyAuth\Contracts\EasyAuthUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['uuid'])]
class Device extends Model
{
    /**
     * The user this device belongs to.
     *
     * @return BelongsTo<EasyAuthUser, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}

<?php

namespace App\Models;

use App\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Passkeys\Passkey as BasePasskey;

class Passkey extends BasePasskey
{
    use HasPublicUuid;

    protected $hidden = [
        'id',
        'user_id',
        'credential_id',
        'credential',
    ];

    // withTrashed() so a passkey login of a soft-deleted user resolves the owner
    // and can show the deactivation notice (see FortifyServiceProvider).
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}

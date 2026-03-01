<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Misaf\VendraUser\Models\User;

trait BelongsToUser
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

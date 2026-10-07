<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Support\Facades\DB;
use Misaf\VendraUser\Models\User;

final readonly class DeleteUserAction
{
    public function __construct(private SetUserAccountEnabledAction $setAccountEnabled) {}

    public function execute(User $user): bool
    {
        $tenant = $user->tenant()->first();

        if ($tenant !== null) {
            return $this->setAccountEnabled->execute($tenant, $user, false)->trashed();
        }

        return DB::transaction(fn (): bool => (bool) $user->refreshForUpdate()->delete());
    }
}

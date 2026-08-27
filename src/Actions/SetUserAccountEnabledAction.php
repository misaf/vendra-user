<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\TenantAdministratorGuard;

final class SetUserAccountEnabledAction
{
    public function __construct(private readonly TenantAdministratorGuard $guard) {}

    public function execute(Model $tenant, User $user, bool $enabled): User
    {
        return DB::transaction(fn(): User => $this->guard->execute($tenant, function () use ($tenant, $user, $enabled): User {
            $lockedUser = User::query()
                ->withTrashed()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->guard->assertBelongsToTenant($lockedUser, $tenant);

            if ($enabled) {
                $lockedUser->restore();

                return $lockedUser->refresh();
            }

            if ( ! $lockedUser->trashed()) {
                $this->guard->assertMayRemoveAdministrator($lockedUser, $tenant);
                $lockedUser->delete();
            }

            return $lockedUser;
        }));
    }
}

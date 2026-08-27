<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\TenantAdministratorGuard;

final class DemoteTenantAdministratorAction
{
    public function __construct(private readonly TenantAdministratorGuard $guard) {}

    public function execute(Model $tenant, User $user): User
    {
        return DB::transaction(fn(): User => $this->guard->execute($tenant, function () use ($tenant, $user): User {
            $this->guard->assertBelongsToTenant($user, $tenant);
            $this->guard->assertMayRemoveAdministrator($user, $tenant);

            $user->removeRole($this->guard->roleName());

            return $user->refresh();
        }));
    }
}

<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\TenantAdministratorGuard;

final class RemoveTenantAdministratorAction
{
    public function __construct(private readonly TenantAdministratorGuard $guard) {}

    public function execute(Model $tenant, User $user): void
    {
        DB::transaction(fn(): mixed => $this->guard->execute($tenant, function () use ($tenant, $user): void {
            $this->guard->assertBelongsToTenant($user, $tenant);
            $this->guard->assertMayRemoveAdministrator($user, $tenant);

            if ($user->hasRole($this->guard->roleName())) {
                $user->removeRole($this->guard->roleName());
            }

            $user->tenants()->detach($tenant->getKey());
        }));
    }
}

<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use LogicException;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraUser\Exceptions\LastAdministratorException;
use Misaf\VendraUser\Models\User;

final class TenantAdministratorGuard
{
    public function __construct(private readonly TenantResolver $tenantResolver) {}

    public function execute(Model $tenant, callable $callback): mixed
    {
        return $this->tenantResolver->execute($tenant, function () use ($tenant, $callback): mixed {
            $tenant->newQuery()
                ->whereKey($tenant->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            return $callback();
        });
    }

    public function assertBelongsToTenant(User $user, Model $tenant): void
    {
        if ($user->tenant()->whereKey($tenant->getKey())->doesntExist()) {
            throw new LogicException("User [{$user->id}] does not belong to tenant [{$tenant->getKey()}].");
        }
    }

    public function assertMayRemoveAdministrator(User $user, Model $tenant): void
    {
        if ( ! $user->hasRole($this->roleName())) {
            return;
        }

        $administratorCount = User::query()
            ->whereHas('tenants', fn($query) => $query->whereKey($tenant->getKey()))
            ->role($this->roleName())
            ->lockForUpdate()
            ->count();

        if ($administratorCount <= 1) {
            throw LastAdministratorException::forTenant($tenant->getKey());
        }
    }

    public function roleName(): string
    {
        return Config::string('vendra-permission.admin_role');
    }
}

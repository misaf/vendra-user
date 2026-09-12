<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Support;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use LogicException;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraUser\Exceptions\LastAdministratorException;
use Misaf\VendraUser\Models\User;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class TenantAdministratorGuard
{
    public function __construct(private TenantResolver $tenantResolver) {}

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
        if (! $user->hasRole($this->roleName())) {
            return;
        }

        $administratorCount = User::query()
            ->whereHas('tenants', fn (Builder $query) => $query->whereKey($tenant->getKey()))
            ->role($this->role())
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

    /**
     * The admin role as a model, resolved for the tenant-facing guard.
     *
     * String-based role operations resolve their guard from the ambient
     * default guard, which Filament switches per panel (`console` inside
     * the console panel). Tenant administration runs from the admin panel
     * but also from the console panel's store managers, so the guard must
     * be explicit: tenant admin roles live on the `web` guard.
     */
    public function role(): Role
    {
        $roleModelClass = resolve(PermissionRegistrar::class)->getRoleClass();

        throw_unless(is_a($roleModelClass, Role::class, true), LogicException::class, "The configured role model [{$roleModelClass}] must implement the role contract.");

        return $roleModelClass::findByName($this->roleName(), 'web');
    }
}

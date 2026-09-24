<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Support;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function execute(Model $tenant, callable $callback): mixed
    {
        return $this->tenantResolver->execute($tenant, function () use ($tenant, $callback): mixed {
            $tenant->refreshForUpdate();

            throw_if(method_exists($tenant, 'trashed') && $tenant->trashed(), (new ModelNotFoundException)->setModel($tenant::class));

            return $callback();
        });
    }

    public function assertBelongsToTenant(User $user, Model $tenant): void
    {
        if ($user->tenant()->whereKey($tenant->getKey())->doesntExist()) {
            throw new LogicException("User [{$user->id}] does not belong to tenant [{$this->tenantKey($tenant)}].");
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
            throw LastAdministratorException::forTenant($this->tenantKey($tenant));
        }
    }

    public function roleName(): string
    {
        return Config::string('vendra-permission.admin_role');
    }

    /**
     * Get the admin role on the `web` guard.
     *
     * The guard is explicit because Filament switches the default guard per panel.
     */
    public function role(): Role
    {
        $roleModelClass = resolve(PermissionRegistrar::class)->getRoleClass();

        throw_unless(is_a($roleModelClass, Role::class, true), LogicException::class, "The configured role model [{$roleModelClass}] must implement the role contract.");

        return $roleModelClass::findByName($this->roleName(), 'web');
    }

    private function tenantKey(Model $tenant): int|string
    {
        $key = $tenant->getKey();

        throw_unless(is_int($key) || is_string($key), LogicException::class, 'A tenant must have a key.');

        return $key;
    }
}

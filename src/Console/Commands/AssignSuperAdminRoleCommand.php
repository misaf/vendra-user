<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Config;
use LogicException;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraSupport\Tenancy\Scopes\TeamScope;
use Misaf\VendraSupport\Tenancy\Scopes\TenantScope;
use Misaf\VendraUser\Models\User;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\PermissionRegistrar;

final class AssignSuperAdminRoleCommand extends Command implements PromptsForMissingInput
{
    protected $signature = 'user:assign-super-admin
        {user_id=1 : The ID of the user to assign the super-admin role to}
        {--tenant= : Optional tenant ID or slug; inferred from the user when omitted}';

    protected $description = 'Assign the super-admin role to a specific user';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $tenantResolver = app(TenantResolver::class);

        if ( ! $tenantResolver->available()) {
            return $this->assignSuperAdminRole($userId);
        }

        $tenantIdentifier = (string) $this->option('tenant');

        if ('' === $tenantIdentifier) {
            $user = User::query()
                ->withoutGlobalScopes([TenantScope::class, TeamScope::class])
                ->find($userId);

            if ( ! $user) {
                $this->error("User with ID {$userId} not found.");

                return self::FAILURE;
            }

            $tenantIdentifier = (string) $user->tenant_id;
        }

        $tenant = $tenantResolver->findByKeyOrSlug($tenantIdentifier);

        if ( ! $tenant) {
            $this->error("Tenant [{$tenantIdentifier}] not found.");

            return self::FAILURE;
        }

        $exitCode = $tenantResolver->execute(
            $tenant,
            fn(): int => $this->assignSuperAdminRole($userId),
        );

        if ( ! is_int($exitCode)) {
            throw new LogicException('The tenant resolver returned an invalid command exit code.');
        }

        return $exitCode;
    }

    private function assignSuperAdminRole(int $userId): int
    {
        $roleName = Config::string('vendra-permission.super_admin_role', 'super-admin');

        $user = User::query()->find($userId);

        if ( ! $user) {
            $this->error("User with ID {$userId} not found.");

            return self::FAILURE;
        }

        $guardName = Guard::getDefaultName($user);

        try {
            $superAdminRole = $this->roleModelClass()::findByName($roleName, $guardName);
        } catch (RoleDoesNotExist) {
            $this->error("Super-admin role [{$roleName}] with guard [{$guardName}] not found. Please run the PermissionSeeder first.");

            return self::FAILURE;
        }

        if ($user->hasRole($superAdminRole)) {
            $this->info("User {$user->username} (ID: {$userId}) already has the super-admin role [{$roleName}].");

            return self::SUCCESS;
        }

        $user->assignRole($superAdminRole);

        $this->info("Successfully assigned super-admin role [{$roleName}] to user {$user->username} (ID: {$userId}).");

        return self::SUCCESS;
    }

    /**
     * @return class-string<Role>
     */
    private function roleModelClass(): string
    {
        $roleModelClass = app(PermissionRegistrar::class)->getRoleClass();

        if ( ! is_a($roleModelClass, Role::class, true)) {
            throw new LogicException("The configured role model [{$roleModelClass}] must implement the role contract.");
        }

        return $roleModelClass;
    }
}

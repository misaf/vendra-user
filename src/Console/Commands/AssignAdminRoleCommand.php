<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Console\Commands;

use Closure;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use LogicException;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraSupport\Tenancy\Scopes\TeamScope;
use Misaf\VendraSupport\Tenancy\Scopes\TenantScope;
use Misaf\VendraUser\Actions\PromoteTenantAdministratorAction;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\TenantAdministratorGuard;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

/**
 * The admin role always lives on the `web` guard, as it does when a store is
 * provisioned, whatever the ambient default guard is.
 */
#[Description('Assign the admin role to a specific user')]
#[Signature('vendra-user:assign-admin
        {user_id=1 : The ID of the user to assign the admin role to}
        {--tenant= : Optional tenant ID or slug; inferred from the user when omitted}')]
final class AssignAdminRoleCommand extends Command implements PromptsForMissingInput
{
    public function __construct(
        private readonly TenantAdministratorGuard $administratorGuard,
        private readonly PromoteTenantAdministratorAction $promoteTenantAdministratorAction,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $tenantResolver = resolve(TenantResolver::class);

        if (! $tenantResolver->available()) {
            return $this->assignAdminRole($userId, function (User $user): void {
                $user->assignRole($this->administratorGuard->role());
            });
        }

        $tenantIdentifier = (string) $this->option('tenant');

        if ($tenantIdentifier === '') {
            $user = User::query()
                ->withoutGlobalScopes([TenantScope::class, TeamScope::class])
                ->find($userId);

            if (! $user) {
                $this->error("User with ID {$userId} not found.");

                return self::FAILURE;
            }

            $tenantIdentifier = (string) $user->tenant_id;
        }

        $tenant = $tenantResolver->findByKeyOrSlug($tenantIdentifier);

        if (! $tenant) {
            $this->error("Tenant [{$tenantIdentifier}] not found.");

            return self::FAILURE;
        }

        $exitCode = $tenantResolver->execute(
            $tenant,
            fn (): int => $this->assignAdminRole($userId, function (User $user) use ($tenant): void {
                $this->promoteTenantAdministratorAction->execute($tenant, $user);
            }),
        );

        throw_unless(is_int($exitCode), LogicException::class, 'The tenant resolver returned an invalid command exit code.');

        return $exitCode;
    }

    /**
     * @param  Closure(User): void  $assign
     */
    private function assignAdminRole(int $userId, Closure $assign): int
    {
        $roleName = $this->administratorGuard->roleName();

        $user = User::query()->find($userId);

        if (! $user) {
            $this->error("User with ID {$userId} not found.");

            return self::FAILURE;
        }

        if ($user->hasRole($roleName, 'web')) {
            $this->info("User {$user->username} (ID: {$userId}) already has the admin role [{$roleName}].");

            return self::SUCCESS;
        }

        try {
            $assign($user);
        } catch (RoleDoesNotExist) {
            $this->error("Admin role [{$roleName}] with guard [web] not found. Please run the PermissionSeeder first.");

            return self::FAILURE;
        }

        $this->info("Successfully assigned admin role [{$roleName}] to user {$user->username} (ID: {$userId}).");

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Misaf\VendraSupport\Contracts\TenantEntitlements;
use Misaf\VendraSupport\Enums\PlanLimit;
use Misaf\VendraSupport\Exceptions\EntitlementExceededException;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\TenantAdministratorGuard;

final readonly class AddTenantAdministratorAction
{
    public function __construct(
        private CreateUserAction $createUserAction,
        private TenantAdministratorGuard $guard,
        private TenantEntitlements $entitlements,
    ) {}

    /**
     * @throws EntitlementExceededException
     */
    public function execute(
        Model $tenant,
        string $username,
        string $email,
        string $password,
        bool $verified = true,
    ): User {
        return DB::transaction(fn (): User => $this->guard->execute($tenant, function () use ($tenant, $username, $email, $password, $verified): User {
            $this->entitlements->assertCanAdd(PlanLimit::StaffPerStore, tenant: $tenant);

            $user = $this->createUserAction->execute(
                tenant: $tenant,
                username: $username,
                email: $email,
                password: $password,
                isVerified: $verified,
                role: $this->guard->role(),
            );

            $user->tenants()->syncWithoutDetaching([$tenant->getKey()]);

            $this->entitlements->recordAdded(PlanLimit::StaffPerStore, tenant: $tenant);

            return $user;
        }));
    }
}

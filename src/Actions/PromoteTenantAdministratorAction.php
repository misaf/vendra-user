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

final readonly class PromoteTenantAdministratorAction
{
    public function __construct(
        private TenantAdministratorGuard $guard,
        private TenantEntitlements $entitlements,
    ) {}

    /**
     * A user who already holds a role is already counted as staff.
     *
     * @throws EntitlementExceededException
     */
    public function execute(Model $tenant, User $user): User
    {
        return DB::transaction(fn (): User => $this->guard->execute($tenant, function () use ($tenant, $user): User {
            $this->guard->assertBelongsToTenant($user, $tenant);

            $becomesStaff = $user->roles()->doesntExist();

            if ($becomesStaff) {
                $this->entitlements->assertCanAdd(PlanLimit::StaffPerStore, tenant: $tenant);
            }

            $user->tenants()->syncWithoutDetaching([$tenant->getKey()]);
            $user->assignRole($this->guard->role());

            if ($becomesStaff) {
                $this->entitlements->recordAdded(PlanLimit::StaffPerStore, tenant: $tenant);
            }

            return $user->refresh();
        }));
    }
}

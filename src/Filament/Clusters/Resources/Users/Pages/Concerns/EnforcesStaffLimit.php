<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\Concerns;

use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Arr;
use Misaf\VendraSupport\Contracts\TenantEntitlements;
use Misaf\VendraSupport\Enums\PlanLimit;
use Misaf\VendraSupport\Exceptions\EntitlementExceededException;

/**
 * A user becomes staff, and counts toward the plan's staff limit, once it holds a role.
 */
trait EnforcesStaffLimit
{
    protected function assignsRoles(): bool
    {
        return Arr::wrap(Arr::get($this->data ?? [], 'roles')) !== [];
    }

    /**
     * @throws Halt
     */
    protected function assertRoomForStaff(): void
    {
        try {
            resolve(TenantEntitlements::class)->assertCanAdd(PlanLimit::StaffPerStore);
        } catch (EntitlementExceededException $exception) {
            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();

            throw new Halt;
        }
    }

    protected function recordStaffAdded(): void
    {
        resolve(TenantEntitlements::class)->recordAdded(PlanLimit::StaffPerStore);
    }
}

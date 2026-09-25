<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Misaf\VendraSupport\Contracts\TenantEntitlements;
use Misaf\VendraSupport\Enums\PlanFeature;
use Misaf\VendraSupport\Enums\PlanLimit;
use Misaf\VendraSupport\Exceptions\EntitlementExceededException;
use Misaf\VendraSupport\Tenancy\TenantUsageRegistry;
use Misaf\VendraUser\Actions\AddTenantAdministratorAction;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\CreateUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\EditUser;
use Misaf\VendraUser\Models\User;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->tenant = setUpFilamentAdminTestContext();
});

function capStaffAtCurrentUsage(): void
{
    app()->instance(TenantEntitlements::class, new class implements TenantEntitlements
    {
        public function allows(PlanFeature $feature, ?Model $tenant = null): bool
        {
            return true;
        }

        public function limit(PlanLimit $limit, ?Model $tenant = null): ?int
        {
            return 0;
        }

        public function assertAllows(PlanFeature $feature, ?Model $tenant = null): void {}

        public function canAdd(PlanLimit $limit, int $amount = 1, ?Model $tenant = null): bool
        {
            return false;
        }

        public function assertCanAdd(PlanLimit $limit, int $amount = 1, ?Model $tenant = null): void
        {
            throw EntitlementExceededException::limitReached($limit, 0);
        }

        public function recordAdded(PlanLimit $limit, int $amount = 1, ?Model $tenant = null): void {}
    });
}

function staffLimitMessage(): string
{
    return __('vendra-support::entitlements.limit_reached', ['limit' => PlanLimit::StaffPerStore->getLabel(), 'allowed' => 0]);
}

it('counts only the store users who hold a role as staff', function (): void {
    User::factory()->count(2)->create();

    expect(resolve(TenantUsageRegistry::class)->usage(PlanLimit::StaffPerStore, $this->tenant))->toBe(1);
});

it('refuses another administrator past the staff limit', function (): void {
    capStaffAtCurrentUsage();

    resolve(AddTenantAdministratorAction::class)->execute($this->tenant, 'second_admin', 'second@example.test', 'SecurePassword123');
})->throws(EntitlementExceededException::class);

it('refuses a user with a role on the create page past the staff limit but still creates customers', function (): void {
    $roleClass = resolve(PermissionRegistrar::class)->getRoleClass();
    $role = $roleClass::findByName(Config::string('vendra-permission.admin_role'), 'web');
    capStaffAtCurrentUsage();

    livewire(CreateUser::class)
        ->fillForm(['username' => 'new-staff', 'email' => 'new-staff@gmail.com', 'password' => 'secret-password', 'roles' => [$role->getKey()]])
        ->call('create')
        ->assertNotified(staffLimitMessage());

    expect(User::query()->where('email', 'new-staff@gmail.com')->exists())->toBeFalse();

    livewire(CreateUser::class)
        ->fillForm(['username' => 'customer', 'email' => 'customer@gmail.com', 'password' => 'secret-password'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->where('email', 'customer@gmail.com')->exists())->toBeTrue();
});

it('disables the roles field at the staff limit except for existing staff', function (): void {
    capStaffAtCurrentUsage();

    livewire(CreateUser::class)
        ->assertFormFieldDisabled('roles')
        ->assertSee(staffLimitMessage());

    livewire(EditUser::class, ['record' => User::factory()->create()->getRouteKey()])
        ->assertFormFieldDisabled('roles');

    $staff = User::query()->whereHas('roles')->firstOrFail();

    livewire(EditUser::class, ['record' => $staff->getRouteKey()])
        ->assertFormFieldEnabled('roles');
});

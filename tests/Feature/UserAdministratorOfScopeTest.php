<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraUser\Actions\AddTenantAdministratorAction;
use Misaf\VendraUser\Actions\DemoteTenantAdministratorAction;
use Misaf\VendraUser\Models\User;
use Spatie\Permission\PermissionRegistrar;

function tenantWithAdministratorRole(): Model
{
    $tenant = createTestTenant();
    $roleClass = resolve(PermissionRegistrar::class)->getRoleClass();

    resolve(TenantResolver::class)->execute($tenant, fn (): mixed => $roleClass::query()->firstOrCreate([
        'name' => Config::string('vendra-permission.admin_role'),
        'guard_name' => 'web',
    ]));

    return $tenant;
}

it('keeps only users holding the given tenant admin role', function (): void {
    $tenant = tenantWithAdministratorRole();
    $otherTenant = tenantWithAdministratorRole();
    $addAdministrator = resolve(AddTenantAdministratorAction::class);

    $administrator = $addAdministrator->execute($tenant, 'store_admin', 'admin@example.com', 'SecurePassword123');
    $member = $addAdministrator->execute($tenant, 'store_member', 'member@example.com', 'SecurePassword123');
    $addAdministrator->execute($tenant, 'keeps_last', 'last@example.com', 'SecurePassword123');
    resolve(DemoteTenantAdministratorAction::class)->execute($tenant, $member);
    $otherAdministrator = $addAdministrator->execute($otherTenant, 'other_admin', 'other@example.com', 'SecurePassword123');

    $administratorIds = User::query()->withoutGlobalScopes()->administratorOf($tenant)->pluck('id');

    expect($administratorIds)->toContain($administrator->getKey())
        ->not->toContain($member->getKey())
        ->not->toContain($otherAdministrator->getKey());
});

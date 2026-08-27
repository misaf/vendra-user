<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraUser\Actions\AddTenantAdministratorAction;
use Misaf\VendraUser\Actions\CreateUserAction;
use Misaf\VendraUser\Actions\DemoteTenantAdministratorAction;
use Misaf\VendraUser\Actions\PromoteTenantAdministratorAction;
use Misaf\VendraUser\Actions\RemoveTenantAdministratorAction;
use Misaf\VendraUser\Actions\SetUserAccountEnabledAction;
use Misaf\VendraUser\Actions\UpdateUserEmailAction;
use Misaf\VendraUser\Actions\UpdateUserPasswordAction;
use Misaf\VendraUser\Exceptions\LastAdministratorException;
use Spatie\Permission\PermissionRegistrar;

function prepareAdministratorRole(Model $tenant): void
{
    $roleClass = app(PermissionRegistrar::class)->getRoleClass();

    app(TenantResolver::class)->execute(
        $tenant,
        fn(): mixed => $roleClass::query()->firstOrCreate([
            'name'       => Config::string('vendra-permission.admin_role'),
            'guard_name' => 'web',
        ]),
    );
}

it('adds a tenant administrator with a hashed password and membership', function (): void {
    $tenant = createTestTenant();
    prepareAdministratorRole($tenant);

    $administrator = app(AddTenantAdministratorAction::class)->execute(
        $tenant,
        'store_admin',
        'ADMIN@EXAMPLE.COM',
        'SecurePassword123',
    );

    expect($administrator->email)->toBe('admin@example.com')
        ->and(Hash::check('SecurePassword123', $administrator->password))->toBeTrue()
        ->and($administrator->tenants()->whereKey($tenant->getKey())->exists())->toBeTrue()
        ->and($administrator->hasRole(Config::string('vendra-permission.admin_role')))->toBeTrue();
});

it('updates administrator credentials through domain actions', function (): void {
    $tenant = createTestTenant();
    prepareAdministratorRole($tenant);
    $administrator = app(AddTenantAdministratorAction::class)->execute(
        $tenant,
        'store_admin',
        'admin@example.com',
        'OldPassword123',
    );
    $rememberToken = $administrator->getRememberToken();

    app(UpdateUserPasswordAction::class)->execute($administrator, 'NewPassword123');
    app(UpdateUserEmailAction::class)->execute($administrator, 'NEW@EXAMPLE.COM');

    $administrator->refresh();

    expect(Hash::check('NewPassword123', $administrator->password))->toBeTrue()
        ->and($administrator->getRememberToken())->not->toBe($rememberToken)
        ->and($administrator->email)->toBe('new@example.com')
        ->and($administrator->email_verified_at)->not->toBeNull();
});

it('prevents the last enabled administrator from being demoted removed or disabled', function (): void {
    $tenant = createTestTenant();
    prepareAdministratorRole($tenant);
    $administrator = app(AddTenantAdministratorAction::class)->execute(
        $tenant,
        'only_admin',
        'only@example.com',
        'SecurePassword123',
    );

    expect(fn() => app(DemoteTenantAdministratorAction::class)->execute($tenant, $administrator))
        ->toThrow(LastAdministratorException::class)
        ->and(fn() => app(RemoveTenantAdministratorAction::class)->execute($tenant, $administrator))
        ->toThrow(LastAdministratorException::class)
        ->and(fn() => app(SetUserAccountEnabledAction::class)->execute($tenant, $administrator, false))
        ->toThrow(LastAdministratorException::class);
});

it('promotes demotes and disables administrators while another administrator remains', function (): void {
    $tenant = createTestTenant();
    prepareAdministratorRole($tenant);
    $first = app(AddTenantAdministratorAction::class)->execute(
        $tenant,
        'first_admin',
        'first@example.com',
        'SecurePassword123',
    );
    $second = app(CreateUserAction::class)->execute(
        $tenant,
        'second_admin',
        'second@example.com',
        'SecurePassword123',
    );

    app(PromoteTenantAdministratorAction::class)->execute($tenant, $second);
    app(DemoteTenantAdministratorAction::class)->execute($tenant, $first);
    app(SetUserAccountEnabledAction::class)->execute($tenant, $first, false);

    expect($first->newQuery()->find($first->getKey()))->toBeNull()
        ->and($second->refresh()->hasRole(Config::string('vendra-permission.admin_role')))->toBeTrue();

    app(SetUserAccountEnabledAction::class)->execute($tenant, $first, true);

    expect($first->newQuery()->find($first->getKey()))->not->toBeNull();
});

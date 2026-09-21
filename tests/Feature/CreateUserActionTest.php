<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraUser\Actions\CreateUserAction;
use Spatie\Permission\PermissionRegistrar;

it('creates a user within the tenant context and assigns the given role name', function (): void {
    $tenant = createTestTenant();

    $roleClass = resolve(PermissionRegistrar::class)->getRoleClass();

    resolve(TenantResolver::class)->execute(
        $tenant,
        fn (): mixed => $roleClass::create(['name' => 'editor', 'guard_name' => 'web']),
    );

    $user = resolve(CreateUserAction::class)->execute(
        tenant: $tenant,
        username: 'demo-user',
        email: 'demo-user@example.com',
        password: 'secret-password',
        role: 'editor',
    );

    expect($user->tenant_id)->toBe($tenant->getKey())
        ->and($user->hasRole('editor'))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
});

it('creates a tenantless user without a tenant even inside a tenant context', function (): void {
    makeCurrentTestTenant();

    $user = resolve(CreateUserAction::class)->execute(
        tenant: null,
        username: 'tenantless',
        email: 'tenantless@example.com',
        password: 'secret-password',
    );

    expect($user->refresh()->tenant_id)->toBeNull()
        ->and(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
});

it('creates an unverified user without a role', function (): void {
    $tenant = createTestTenant();

    $user = resolve(CreateUserAction::class)->execute(
        tenant: $tenant,
        username: 'plain-user',
        email: 'plain-user@example.com',
        password: 'secret-password',
        isVerified: false,
    );

    expect($user->email_verified_at)->toBeNull()
        ->and($user->roles)->toBeEmpty();
});

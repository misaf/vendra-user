<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraUser\Models\User;

use function Pest\Laravel\assertDatabaseHas;

use function Pest\Laravel\assertDatabaseMissing;

use Spatie\Permission\PermissionRegistrar;

it('infers the tenant and assigns the configured role model and role name', function (): void {
    Config::set('vendra-permission.admin_role', 'platform-owner');

    $otherTenant = createTestTenant();
    $tenant = createTestTenant();
    $tenantResolver = app(TenantResolver::class);
    $roleClass = app(PermissionRegistrar::class)->getRoleClass();

    $otherRole = $tenantResolver->execute(
        $otherTenant,
        fn() => $roleClass::create(['name' => 'platform-owner', 'guard_name' => 'web']),
    );
    $role = $tenantResolver->execute(
        $tenant,
        fn() => $roleClass::create(['name' => 'platform-owner', 'guard_name' => 'web']),
    );
    $user = $tenantResolver->execute(
        $tenant,
        fn(): User => User::factory()->create(['username' => 'tenant-admin']),
    );

    $this->artisan('user:assign-admin', [
        'user_id' => $user->getKey(),
    ])
        ->expectsOutput("Successfully assigned admin role [platform-owner] to user tenant-admin (ID: {$user->getKey()}).")
        ->assertSuccessful();

    assertDatabaseHas('model_has_roles', [
        'role_id'    => $role->getKey(),
        'model_type' => $user->getMorphClass(),
        'model_id'   => $user->getKey(),
    ]);
    assertDatabaseMissing('model_has_roles', [
        'role_id'    => $otherRole->getKey(),
        'model_type' => $user->getMorphClass(),
        'model_id'   => $user->getKey(),
    ]);
});

it('does not resolve a user from another tenant', function (): void {
    $userTenant = createTestTenant();
    $selectedTenant = createTestTenant();
    $tenantResolver = app(TenantResolver::class);
    $roleClass = app(PermissionRegistrar::class)->getRoleClass();

    $user = $tenantResolver->execute(
        $userTenant,
        fn(): User => User::factory()->create(),
    );
    $tenantResolver->execute(
        $selectedTenant,
        fn() => $roleClass::create(['name' => 'admin', 'guard_name' => 'web']),
    );

    $this->artisan('user:assign-admin', [
        'user_id'  => $user->getKey(),
        '--tenant' => $selectedTenant->getKey(),
    ])
        ->expectsOutput("User with ID {$user->getKey()} not found.")
        ->assertFailed();

    expect($user->roles()->count())->toBe(0);
});

it('fails when the user does not exist in the selected tenant', function (): void {
    $tenant = createTestTenant();

    $this->artisan('user:assign-admin', [
        'user_id'  => 999,
        '--tenant' => $tenant->getKey(),
    ])
        ->expectsOutput('User with ID 999 not found.')
        ->assertFailed();
});

it('fails when the configured role does not exist for the selected tenant', function (): void {
    $tenant = createTestTenant();
    $tenantResolver = app(TenantResolver::class);
    $user = $tenantResolver->execute(
        $tenant,
        fn(): User => User::factory()->create(),
    );

    $this->artisan('user:assign-admin', [
        'user_id'  => $user->getKey(),
        '--tenant' => $tenant->getKey(),
    ])
        ->expectsOutput('Admin role [admin] with guard [web] not found. Please run the PermissionSeeder first.')
        ->assertFailed();

    expect($user->roles()->count())->toBe(0);
});

it('uses the user model default guard', function (): void {
    Config::set('auth.defaults.guard', 'sanctum');

    $tenant = createTestTenant();
    $tenantResolver = app(TenantResolver::class);
    $roleClass = app(PermissionRegistrar::class)->getRoleClass();
    $role = $tenantResolver->execute(
        $tenant,
        fn() => $roleClass::create(['name' => 'admin', 'guard_name' => 'sanctum']),
    );
    $user = $tenantResolver->execute(
        $tenant,
        fn(): User => User::factory()->create(),
    );

    $this->artisan('user:assign-admin', [
        'user_id'  => $user->getKey(),
        '--tenant' => $tenant->getKey(),
    ])->assertSuccessful();

    assertDatabaseHas('model_has_roles', [
        'role_id'    => $role->getKey(),
        'model_type' => $user->getMorphClass(),
        'model_id'   => $user->getKey(),
    ]);
});

it('does not duplicate an existing assignment', function (): void {
    $tenant = createTestTenant();
    $tenantResolver = app(TenantResolver::class);
    $roleClass = app(PermissionRegistrar::class)->getRoleClass();
    $role = $tenantResolver->execute(
        $tenant,
        fn() => $roleClass::create(['name' => 'admin', 'guard_name' => 'web']),
    );
    $user = $tenantResolver->execute(
        $tenant,
        function () use ($role): User {
            $user = User::factory()->create(['username' => 'existing-admin']);
            $user->assignRole($role);

            return $user;
        },
    );

    $this->artisan('user:assign-admin', [
        'user_id'  => $user->getKey(),
        '--tenant' => $tenant->getKey(),
    ])
        ->expectsOutput("User existing-admin (ID: {$user->getKey()}) already has the admin role [admin].")
        ->assertSuccessful();

    expect($user->roles()->count())->toBe(1);
});

it('fails when the selected tenant does not exist', function (): void {
    $this->artisan('user:assign-admin', [
        'user_id'  => 1,
        '--tenant' => 999,
    ])
        ->expectsOutput('Tenant [999] not found.')
        ->assertFailed();
});

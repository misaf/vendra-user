<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Misaf\VendraUser\Auth\TenantlessUserProvider;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\PanelAccessRegistry;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;

function tenantlessProviderUserSuite(): TenantlessUserProvider
{
    $provider = auth('console')->getProvider();

    expect($provider)->toBeInstanceOf(TenantlessUserProvider::class);

    return $provider;
}

it('scopes credential lookup to tenantless identities when emails collide', function (): void {
    $tenant = createTestTenant();
    $email = 'shared@example.test';

    $tenantUser = User::factory()->forTenant($tenant)->create([
        'username' => 'tenant_user',
        'email' => $email,
        'password' => Hash::make('tenant-password'),
    ]);

    $tenantlessUser = User::factory()->create([
        'tenant_id' => null,
        'username' => 'tenantless_user',
        'email' => $email,
        'password' => Hash::make('tenantless-password'),
    ]);

    expect($tenantlessUser->tenant_id)->toBeNull();

    $provider = tenantlessProviderUserSuite();

    $byCredentials = $provider->retrieveByCredentials(['email' => $email]);

    expect($byCredentials?->getKey())->toBe($tenantlessUser->getKey())
        ->and($provider->validateCredentials($byCredentials, ['password' => 'tenantless-password']))->toBeTrue()
        ->and($provider->validateCredentials($byCredentials, ['password' => 'tenant-password']))->toBeFalse();

    // The plain users provider keeps its existing tenant-agnostic behavior.
    $webUser = auth('web')->getProvider()->retrieveByCredentials(['email' => $email]);

    expect(auth('web')->getProvider())->not->toBeInstanceOf(TenantlessUserProvider::class)
        ->and($webUser)->not->toBeNull()
        ->and($tenantUser->getKey())->not->toBe($tenantlessUser->getKey());
});

it('resolves tenantless users by id and remember token, never tenant rows', function (): void {
    $tenant = createTestTenant();
    $email = 'token-shared@example.test';

    $tenantUser = User::factory()->forTenant($tenant)->create([
        'username' => 'token_tenant',
        'email' => $email,
        'remember_token' => 'tenant-token',
    ]);

    $tenantlessUser = User::factory()->create([
        'tenant_id' => null,
        'username' => 'token_tenantless',
        'email' => $email,
        'remember_token' => 'tenantless-token',
    ]);

    $provider = tenantlessProviderUserSuite();

    expect($provider->retrieveById($tenantlessUser->getKey())?->getKey())->toBe($tenantlessUser->getKey())
        ->and($provider->retrieveById($tenantUser->getKey()))->toBeNull()
        ->and($provider->retrieveByToken($tenantlessUser->getKey(), 'tenantless-token')?->getKey())->toBe($tenantlessUser->getKey())
        ->and($provider->retrieveByToken($tenantUser->getKey(), 'tenant-token'))->toBeNull()
        ->and($provider->retrieveByToken($tenantlessUser->getKey(), 'wrong-token'))->toBeNull();
});

it('keeps web, console, and reseller sessions isolated', function (): void {
    $tenant = createTestTenant();

    $tenantUser = User::factory()->forTenant($tenant)->create();
    $tenantlessUser = User::factory()->create(['tenant_id' => null]);

    $this->actingAs($tenantlessUser, 'console');

    expect(auth('console')->id())->toBe($tenantlessUser->getKey())
        ->and(auth('web')->check())->toBeFalse()
        ->and(auth('reseller')->check())->toBeFalse();

    auth()->forgetGuards();

    $this->actingAs($tenantUser, 'web');

    expect(auth('web')->id())->toBe($tenantUser->getKey())
        ->and(auth('console')->check())->toBeFalse()
        ->and(auth('reseller')->check())->toBeFalse();
});

it('uses the tenantless provider with a per-panel broker for tenantless guards only', function (): void {
    expect(auth('console')->getProvider())->toBeInstanceOf(TenantlessUserProvider::class)
        ->and(auth('reseller')->getProvider())->toBeInstanceOf(TenantlessUserProvider::class)
        ->and(auth('web')->getProvider())->not->toBeInstanceOf(TenantlessUserProvider::class)
        ->and(config('auth.guards.console.provider'))->toBe('console')
        ->and(config('auth.guards.reseller.provider'))->toBe('reseller')
        ->and(config('auth.guards.web.provider'))->toBe('users')
        ->and(config('auth.providers.console.driver'))->toBe('tenantless-eloquent')
        ->and(config('auth.providers.reseller.driver'))->toBe('tenantless-eloquent')
        ->and(Filament::getPanel('console')->getAuthPasswordBroker())->toBe('console')
        ->and(Filament::getPanel('reseller')->getAuthPasswordBroker())->toBe('reseller')
        ->and(Filament::getPanel('admin')->getAuthPasswordBroker())->toBe('users');
});

it('resolves tenantless password resets to the tenantless user on duplicate emails', function (): void {
    $tenant = createTestTenant();
    $email = 'reset-shared@example.test';

    User::factory()->forTenant($tenant)->create([
        'username' => 'reset_tenant',
        'email' => $email,
        'password' => Hash::make('tenant-password'),
    ]);

    $tenantlessUser = User::factory()->create([
        'tenant_id' => null,
        'username' => 'reset_tenantless',
        'email' => $email,
        'password' => Hash::make('tenantless-password'),
    ]);

    $resolved = Password::broker('console')->getUser(['email' => $email]);

    expect($resolved?->getKey())->toBe($tenantlessUser->getKey());
});

it('delegates non-admin panel access to registered resolvers and denies unclaimed panels', function (): void {
    $registry = resolve(PanelAccessRegistry::class);

    $user = User::factory()->create(['tenant_id' => null]);
    $panel = Filament::getPanel('console');

    expect($registry->has('console'))->toBeTrue()
        ->and($user->canAccessPanel($panel))->toBeFalse();

    $fresh = new PanelAccessRegistry;

    expect($fresh->has('console'))->toBeFalse()
        ->and($fresh->canAccess($user, $panel))->toBeNull();
});

/*
| The `web` guard keeps the unscoped Eloquent provider, so its protection
| against duplicate emails is the tenant context, not the provider. Every
| surface authenticating `web` (the admin panel, API Platform, the MCP
| transport) runs behind `NeedsTenant`, and `User` carries `TenantScope`, so
| the lookup is constrained to the current tenant — where
| `users_active_email_unique` makes the email unambiguous and tenantless rows
| (`tenant_id IS NULL`) are out of reach. This test pins that invariant; it is
| not a description of the provider.
*/
it('resolves web credentials through the tenant context, never the tenantless row', function (): void {
    $email = 'web-scope-shared@example.test';

    $tenant = createTestTenant();

    // Create the tenantless user before a tenant is current, like a console user.
    $tenantlessUser = User::factory()->create([
        'tenant_id' => null,
        'username' => 'web_scope_tenantless',
        'email' => $email,
        'password' => Hash::make('tenantless-password'),
    ]);

    $tenantUser = User::factory()->forTenant($tenant)->create([
        'username' => 'web_scope_tenant',
        'email' => $email,
        'password' => Hash::make('tenant-password'),
    ]);

    switchToTestTenant($tenant);

    $webUser = auth('web')->getProvider()->retrieveByCredentials(['email' => $email]);

    expect($webUser?->getKey())->toBe($tenantUser->getKey())
        ->and($webUser?->getKey())->not->toBe($tenantlessUser->getKey());

    $adminMiddleware = Filament::getPanel('admin')->getMiddleware();

    expect($adminMiddleware)->toContain(NeedsTenant::class)
        ->and(collect(config('api-platform.defaults.middleware'))->contains(NeedsTenant::class))->toBeTrue();
});

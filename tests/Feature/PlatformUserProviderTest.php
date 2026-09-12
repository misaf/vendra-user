<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Misaf\VendraUser\Auth\PlatformUserProvider;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\PanelAccessRegistry;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;

function platformProviderUserSuite(): PlatformUserProvider
{
    $provider = auth('console')->getProvider();

    expect($provider)->toBeInstanceOf(PlatformUserProvider::class);

    return $provider;
}

it('scopes credential lookup to platform identities when emails collide', function (): void {
    $tenant = createTestTenant();
    $email = 'shared@example.test';

    $tenantUser = User::factory()->forTenant($tenant)->create([
        'username' => 'tenant_user',
        'email' => $email,
        'password' => Hash::make('tenant-password'),
    ]);

    $platformUser = User::factory()->create([
        'tenant_id' => null,
        'username' => 'platform_user',
        'email' => $email,
        'password' => Hash::make('platform-password'),
    ]);

    expect($platformUser->tenant_id)->toBeNull();

    $provider = platformProviderUserSuite();

    $byCredentials = $provider->retrieveByCredentials(['email' => $email]);

    expect($byCredentials?->getKey())->toBe($platformUser->getKey())
        ->and($provider->validateCredentials($byCredentials, ['password' => 'platform-password']))->toBeTrue()
        ->and($provider->validateCredentials($byCredentials, ['password' => 'tenant-password']))->toBeFalse();

    // The plain users provider keeps its existing tenant-agnostic behavior.
    $webUser = auth('web')->getProvider()->retrieveByCredentials(['email' => $email]);

    expect(auth('web')->getProvider())->not->toBeInstanceOf(PlatformUserProvider::class)
        ->and($webUser)->not->toBeNull()
        ->and($tenantUser->getKey())->not->toBe($platformUser->getKey());
});

it('resolves platform users by id and remember token, never tenant rows', function (): void {
    $tenant = createTestTenant();
    $email = 'token-shared@example.test';

    $tenantUser = User::factory()->forTenant($tenant)->create([
        'username' => 'token_tenant',
        'email' => $email,
        'remember_token' => 'tenant-token',
    ]);

    $platformUser = User::factory()->create([
        'tenant_id' => null,
        'username' => 'token_platform',
        'email' => $email,
        'remember_token' => 'platform-token',
    ]);

    $provider = platformProviderUserSuite();

    expect($provider->retrieveById($platformUser->getKey())?->getKey())->toBe($platformUser->getKey())
        ->and($provider->retrieveById($tenantUser->getKey()))->toBeNull()
        ->and($provider->retrieveByToken($platformUser->getKey(), 'platform-token')?->getKey())->toBe($platformUser->getKey())
        ->and($provider->retrieveByToken($tenantUser->getKey(), 'tenant-token'))->toBeNull()
        ->and($provider->retrieveByToken($platformUser->getKey(), 'wrong-token'))->toBeNull();
});

it('keeps web, console, and reseller sessions isolated', function (): void {
    $tenant = createTestTenant();

    $tenantUser = User::factory()->forTenant($tenant)->create();
    $platformUser = User::factory()->create(['tenant_id' => null]);

    $this->actingAs($platformUser, 'console');

    expect(auth('console')->id())->toBe($platformUser->getKey())
        ->and(auth('web')->check())->toBeFalse()
        ->and(auth('reseller')->check())->toBeFalse();

    auth()->forgetGuards();

    $this->actingAs($tenantUser, 'web');

    expect(auth('web')->id())->toBe($tenantUser->getKey())
        ->and(auth('console')->check())->toBeFalse()
        ->and(auth('reseller')->check())->toBeFalse();
});

it('uses the platform provider with a per-panel broker for platform guards only', function (): void {
    expect(auth('console')->getProvider())->toBeInstanceOf(PlatformUserProvider::class)
        ->and(auth('reseller')->getProvider())->toBeInstanceOf(PlatformUserProvider::class)
        ->and(auth('web')->getProvider())->not->toBeInstanceOf(PlatformUserProvider::class)
        ->and(config('auth.guards.console.provider'))->toBe('console')
        ->and(config('auth.guards.reseller.provider'))->toBe('reseller')
        ->and(config('auth.guards.web.provider'))->toBe('users')
        ->and(config('auth.providers.console.driver'))->toBe('platform-eloquent')
        ->and(config('auth.providers.reseller.driver'))->toBe('platform-eloquent')
        ->and(Filament::getPanel('console')->getAuthPasswordBroker())->toBe('console')
        ->and(Filament::getPanel('reseller')->getAuthPasswordBroker())->toBe('reseller')
        ->and(Filament::getPanel('admin')->getAuthPasswordBroker())->toBe('users');
});

it('resolves platform password resets to the platform user on duplicate emails', function (): void {
    $tenant = createTestTenant();
    $email = 'reset-shared@example.test';

    User::factory()->forTenant($tenant)->create([
        'username' => 'reset_tenant',
        'email' => $email,
        'password' => Hash::make('tenant-password'),
    ]);

    $platformUser = User::factory()->create([
        'tenant_id' => null,
        'username' => 'reset_platform',
        'email' => $email,
        'password' => Hash::make('platform-password'),
    ]);

    $resolved = Password::broker('console')->getUser(['email' => $email]);

    expect($resolved?->getKey())->toBe($platformUser->getKey());
});

it('delegates non-admin panel access to registered resolvers and denies unclaimed panels', function (): void {
    $registry = app(PanelAccessRegistry::class);

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
| `users_active_email_unique` makes the email unambiguous and platform rows
| (`tenant_id IS NULL`) are out of reach. This test pins that invariant; it is
| not a description of the provider.
*/
it('resolves web credentials through the tenant context, never the platform row', function (): void {
    $email = 'web-scope-shared@example.test';

    $tenant = createTestTenant();

    // The platform row is created before a tenant is current, the way a
    // console user or reseller user is.
    $platformUser = User::factory()->create([
        'tenant_id' => null,
        'username' => 'web_scope_platform',
        'email' => $email,
        'password' => Hash::make('platform-password'),
    ]);

    $tenantUser = User::factory()->forTenant($tenant)->create([
        'username' => 'web_scope_tenant',
        'email' => $email,
        'password' => Hash::make('tenant-password'),
    ]);

    switchToTestTenant($tenant);

    $webUser = auth('web')->getProvider()->retrieveByCredentials(['email' => $email]);

    expect($webUser?->getKey())->toBe($tenantUser->getKey())
        ->and($webUser?->getKey())->not->toBe($platformUser->getKey());

    $adminMiddleware = Filament::getPanel('admin')->getMiddleware();

    expect($adminMiddleware)->toContain(NeedsTenant::class)
        ->and(collect(config('api-platform.defaults.middleware'))->contains(NeedsTenant::class))->toBeTrue();
});

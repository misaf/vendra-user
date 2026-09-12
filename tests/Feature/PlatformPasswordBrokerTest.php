<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Misaf\VendraUser\Models\User;

/**
 * Create a tenant user and a platform user that deliberately share an email.
 *
 * @return array{0: User, 1: User}
 */
function collidingIdentities(string $email, string $prefix): array
{
    $tenant = createTestTenant();

    $tenantUser = User::factory()->forTenant($tenant)->create([
        'username' => $prefix.'_tenant',
        'email' => $email,
        'password' => Hash::make('tenant-password'),
    ]);

    $platformUser = User::factory()->create([
        'tenant_id' => null,
        'username' => $prefix.'_platform',
        'email' => $email,
        'password' => Hash::make('platform-password'),
    ]);

    return [$tenantUser, $platformUser];
}

function resetTokenRows(string $table, string $email): int
{
    return DB::table($table)->where('email', $email)->count();
}

it('gives console and reseller each their own broker and token store', function (): void {
    expect(config('auth.passwords.users.table'))->toBe('password_reset_tokens')
        ->and(config('auth.passwords.console.table'))->toBe('console_password_reset_tokens')
        ->and(config('auth.passwords.reseller.table'))->toBe('reseller_password_reset_tokens')
        ->and(config('auth.passwords.console.provider'))->toBe('console')
        ->and(config('auth.passwords.reseller.provider'))->toBe('reseller')
        ->and(config('auth.passwords.users.expire'))->toBe(config('auth.passwords.console.expire'))
        ->and(config('auth.passwords.users.expire'))->toBe(config('auth.passwords.reseller.expire'))
        ->and(config('auth.passwords.users.throttle'))->toBe(config('auth.passwords.console.throttle'))
        ->and(config('auth.passwords.users.throttle'))->toBe(config('auth.passwords.reseller.throttle'));
});

it('writes a web reset token only to the normal token store', function (): void {
    $email = 'broker-web@example.test';
    [$tenantUser] = collidingIdentities($email, 'broker_web');

    Password::broker('users')->createToken($tenantUser);

    expect(resetTokenRows('password_reset_tokens', $email))->toBe(1)
        ->and(resetTokenRows('console_password_reset_tokens', $email))->toBe(0);
});

it('writes a platform reset token only to the platform token store', function (): void {
    $email = 'broker-platform@example.test';
    [, $platformUser] = collidingIdentities($email, 'broker_platform');

    Password::broker('console')->createToken($platformUser);

    expect(resetTokenRows('console_password_reset_tokens', $email))->toBe(1)
        ->and(resetTokenRows('password_reset_tokens', $email))->toBe(0)
        ->and(resetTokenRows('reseller_password_reset_tokens', $email))->toBe(0);
});

it('keeps console and reseller tokens apart for one platform identity', function (): void {
    $email = 'broker-both-panels@example.test';
    [, $platformUser] = collidingIdentities($email, 'broker_both_panels');

    $consoleToken = Password::broker('console')->createToken($platformUser);
    $resellerToken = Password::broker('reseller')->createToken($platformUser);

    // A reseller reset must never overwrite or consume the console token,
    // even though both brokers resolve the very same user row.
    expect(Password::broker('console')->getRepository()->exists($platformUser, $consoleToken))->toBeTrue()
        ->and(Password::broker('reseller')->getRepository()->exists($platformUser, $resellerToken))->toBeTrue()
        ->and(Password::broker('console')->getRepository()->exists($platformUser, $resellerToken))->toBeFalse()
        ->and(resetTokenRows('console_password_reset_tokens', $email))->toBe(1)
        ->and(resetTokenRows('reseller_password_reset_tokens', $email))->toBe(1);
});

it('keeps both tokens alive when each scope requests one for the same email', function (): void {
    $email = 'broker-both@example.test';
    [$tenantUser, $platformUser] = collidingIdentities($email, 'broker_both');

    $tenantToken = Password::broker('users')->createToken($tenantUser);
    $platformToken = Password::broker('console')->createToken($platformUser);

    // The platform token must not have overwritten or deleted the tenant one.
    expect(Password::broker('users')->getRepository()->exists($tenantUser, $tenantToken))->toBeTrue()
        ->and(Password::broker('console')->getRepository()->exists($platformUser, $platformToken))->toBeTrue();

    $secondTenantToken = Password::broker('users')->createToken($tenantUser);

    // And a later tenant token must not disturb the platform one.
    expect(Password::broker('console')->getRepository()->exists($platformUser, $platformToken))->toBeTrue()
        ->and(Password::broker('users')->getRepository()->exists($tenantUser, $secondTenantToken))->toBeTrue()
        ->and(resetTokenRows('password_reset_tokens', $email))->toBe(1)
        ->and(resetTokenRows('console_password_reset_tokens', $email))->toBe(1);
});

it('refuses a web token against the platform identity', function (): void {
    $email = 'broker-cross-web@example.test';
    [$tenantUser, $platformUser] = collidingIdentities($email, 'broker_cross_web');

    $tenantToken = Password::broker('users')->createToken($tenantUser);

    $status = Password::broker('console')->reset([
        'email' => $email,
        'password' => 'platform-rotated-password',
        'password_confirmation' => 'platform-rotated-password',
        'token' => $tenantToken,
    ], function (User $user): void {
        $user->forceFill(['password' => Hash::make('platform-rotated-password')])->save();
    });

    expect($status)->toBe(Password::INVALID_TOKEN)
        ->and(Hash::check('platform-password', $platformUser->fresh()->password))->toBeTrue();
});

it('refuses a platform token against the tenant identity', function (): void {
    $email = 'broker-cross-platform@example.test';
    [$tenantUser, $platformUser] = collidingIdentities($email, 'broker_cross_platform');

    $platformToken = Password::broker('console')->createToken($platformUser);

    $status = Password::broker('users')->reset([
        'email' => $email,
        'password' => 'tenant-rotated-password',
        'password_confirmation' => 'tenant-rotated-password',
        'token' => $platformToken,
    ], function (User $user): void {
        $user->forceFill(['password' => Hash::make('tenant-rotated-password')])->save();
    });

    expect($status)->toBe(Password::INVALID_TOKEN)
        ->and(Hash::check('tenant-password', $tenantUser->fresh()->password))->toBeTrue();
});

it('resets only the platform identity through its own token', function (): void {
    $email = 'broker-reset@example.test';
    [$tenantUser, $platformUser] = collidingIdentities($email, 'broker_reset');

    $platformToken = Password::broker('console')->createToken($platformUser);

    $resetUser = null;

    $status = Password::broker('console')->reset([
        'email' => $email,
        'password' => 'platform-rotated-password',
        'password_confirmation' => 'platform-rotated-password',
        'token' => $platformToken,
    ], function (User $user) use (&$resetUser): void {
        $resetUser = $user;
        $user->forceFill(['password' => Hash::make('platform-rotated-password')])->save();
    });

    expect($status)->toBe(Password::PASSWORD_RESET)
        ->and($resetUser?->getKey())->toBe($platformUser->getKey())
        ->and(Hash::check('platform-rotated-password', $platformUser->fresh()->password))->toBeTrue()
        ->and(Hash::check('tenant-password', $tenantUser->fresh()->password))->toBeTrue()
        ->and(resetTokenRows('console_password_reset_tokens', $email))->toBe(0);
});

it('expires and throttles platform tokens per scope on Laravel defaults', function (): void {
    Notification::fake();

    $email = 'broker-expiry@example.test';
    [$tenantUser, $platformUser] = collidingIdentities($email, 'broker_expiry');

    $platformToken = Password::broker('console')->createToken($platformUser);

    // Throttling is keyed by the scope's own store, so the platform token
    // must not throttle the tenant identity's first request.
    expect(Password::broker('console')->sendResetLink(['email' => $email]))->toBe(Password::RESET_THROTTLED)
        ->and(Password::broker('users')->sendResetLink(['email' => $email]))->toBe(Password::RESET_LINK_SENT)
        ->and(Password::broker('console')->getRepository()->exists($platformUser, $platformToken))->toBeTrue();

    $this->travel(61)->minutes();

    expect(Password::broker('console')->getRepository()->exists($platformUser, $platformToken))->toBeFalse()
        ->and(Password::broker('console')->sendResetLink(['email' => $email]))->toBe(Password::RESET_LINK_SENT)
        ->and($tenantUser->getKey())->not->toBe($platformUser->getKey());
});

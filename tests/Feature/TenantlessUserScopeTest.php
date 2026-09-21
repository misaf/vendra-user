<?php

declare(strict_types=1);

use Misaf\VendraUser\Models\User;

it('finds the tenantless identity when a tenant user holds the same email', function (): void {
    $tenant = createTestTenant();
    $email = 'shared@example.test';

    $tenantUser = User::factory()->forTenant($tenant)->create([
        'username' => 'tenant_user',
        'email' => $email,
    ]);

    $tenantlessUser = User::factory()->create([
        'tenant_id' => null,
        'username' => 'tenantless_user',
        'email' => $email,
    ]);

    switchToTestTenant($tenant);

    $found = User::query()->tenantless()->where('email', $email)->first();

    expect($found?->getKey())->toBe($tenantlessUser->getKey())
        ->and($found?->getKey())->not->toBe($tenantUser->getKey());
});

it('leaves tenant users out of the tenantless query', function (): void {
    $tenant = createTestTenant();

    User::factory()->forTenant($tenant)->create(['username' => 'only_tenant', 'email' => 'tenant-only@example.test']);

    switchToTestTenant($tenant);

    expect(User::query()->tenantless()->where('email', 'tenant-only@example.test')->exists())->toBeFalse();
});

<?php

declare(strict_types=1);

use Misaf\VendraUser\Models\User;

it('separates verified users from unverified ones', function (): void {
    $verified = User::factory()->create(['tenant_id' => null]);
    $unverified = User::factory()->unverified()->create(['tenant_id' => null]);

    expect(User::query()->tenantless()->verified()->sole()->is($verified))->toBeTrue()
        ->and(User::query()->tenantless()->unverified()->sole()->is($unverified))->toBeTrue();
});

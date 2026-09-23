<?php

declare(strict_types=1);

use Misaf\VendraUser\Models\User;

it('finds the user a single identifier names', function (): void {
    $user = User::factory()->create(['tenant_id' => null, 'username' => 'chosen_name', 'email' => 'ops@example.test']);
    User::factory()->create(['tenant_id' => null]);

    expect(User::query()->tenantless()->identifiedBy(email: 'ops@example.test')->sole()->is($user))->toBeTrue()
        ->and(User::query()->tenantless()->identifiedBy(username: 'chosen_name')->sole()->is($user))->toBeTrue();
});

it('requires both identifiers to name the same user', function (): void {
    $user = User::factory()->create(['tenant_id' => null, 'username' => 'chosen_name', 'email' => 'ops@example.test']);
    User::factory()->create(['tenant_id' => null, 'username' => 'other_name']);

    expect(User::query()->tenantless()->identifiedBy('ops@example.test', 'chosen_name')->sole()->is($user))->toBeTrue()
        ->and(User::query()->tenantless()->identifiedBy('ops@example.test', 'other_name')->exists())->toBeFalse();
});

it('refuses to match every user when no identifier is given', function (): void {
    User::factory()->create(['tenant_id' => null]);

    expect(fn (): ?User => User::query()->tenantless()->identifiedBy()->first())->toThrow(InvalidArgumentException::class);
});

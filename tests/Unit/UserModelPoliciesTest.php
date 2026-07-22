<?php

declare(strict_types=1);

use Misaf\VendraSupport\Traits\BelongsToTenant;
use Misaf\VendraUser\Enums\UserPolicyEnum;
use Misaf\VendraUser\Models\User;

it('applies shared tenant ownership to the user model', function (): void {
    expect(class_uses_recursive(User::class))->toContain(BelongsToTenant::class);
});

it('hides tenant and credential attributes', function (): void {
    expect((new User())->getHidden())->toContain('tenant_id', 'password', 'password_fingerprint', 'remember_token', 'active_email_guard', 'active_reseller_guard');
});

it('defines policy permissions for the user resource', function (): void {
    expect(array_column(UserPolicyEnum::cases(), 'value'))->toHaveCount(10);
});

it('uses kebab-case permission names scoped per model', function (): void {
    $permissions = array_column(UserPolicyEnum::cases(), 'value');

    expect($permissions)->toHaveCount(count(array_unique($permissions)))
        ->each->toMatch('/^[a-z]+(-[a-z]+)*$/');
});

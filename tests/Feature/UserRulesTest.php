<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\UserRules;

it('requires a value held by a tenantless user when no tenant is given', function (): void {
    $tenant = createTestTenant();
    User::factory()->create(['tenant_id' => null, 'email' => 'console@example.test']);
    User::factory()->forTenant($tenant)->create(['email' => 'tenant@example.test']);
    User::factory()->trashed()->create(['tenant_id' => null, 'email' => 'deleted@example.test']);

    $passes = fn (string $email, ?int $tenantId = null): bool => Validator::make(
        ['email' => $email],
        ['email' => [UserRules::exists('email', $tenantId)]],
    )->passes();

    expect($passes('console@example.test'))->toBeTrue()
        ->and($passes('tenant@example.test'))->toBeFalse()
        ->and($passes('deleted@example.test'))->toBeFalse()
        ->and($passes('tenant@example.test', $tenant->getKey()))->toBeTrue()
        ->and($passes('console@example.test', $tenant->getKey()))->toBeFalse();
});

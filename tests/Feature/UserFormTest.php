<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Misaf\VendraUser\Actions\CreateUserAction;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\CreateUser;
use Misaf\VendraUser\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->tenant = setUpFilamentSuperAdminTestContext();
});

it('rejects a duplicate username within the current tenant', function (): void {
    User::factory()->create(['username' => 'demo-user']);

    livewire(CreateUser::class)
        ->fillForm([
            'username' => 'demo-user',
            'email'    => 'demo-user@gmail.com',
            'password' => 'secret-password',
        ])
        ->call('create')
        ->assertHasFormErrors(['username']);
});

it('allows the same username in another tenant', function (): void {
    $otherTenant = createTestTenant();

    // Filament's panel tenancy hook re-associates created users with the
    // panel tenant, so it must point at the foreign tenant while seeding.
    Filament::setTenant($otherTenant);

    $otherUser = app(CreateUserAction::class)->execute(
        tenant: $otherTenant,
        username: 'demo-user',
        email: 'other-tenant@example.com',
        password: 'secret-password',
    );

    Filament::setTenant($this->tenant);

    expect($otherUser->tenant_id)->toBe($otherTenant->getKey());

    livewire(CreateUser::class)
        ->fillForm([
            'username' => 'demo-user',
            'email'    => 'demo-user@gmail.com',
            'password' => 'secret-password',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->withoutGlobalScopes()->where('username', 'demo-user')->count())->toBe(2);
});

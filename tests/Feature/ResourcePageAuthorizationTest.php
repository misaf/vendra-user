<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Misaf\VendraPermission\Database\Factories\RoleFactory;
use Misaf\VendraUser\Database\Factories\UserFactory;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\CreateUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\EditUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\ListUsers;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\ViewUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\UserResource;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    setUpFilamentAdminTestContext();
});

it('renders the create user page under strict authorization', function (): void {
    Filament::getPanel('admin')->strictAuthorization();

    livewire(CreateUser::class)
        ->assertOk();
});

it('renders the list users page under strict authorization', function (): void {
    Filament::getPanel('admin')->strictAuthorization();

    livewire(ListUsers::class)
        ->assertOk();
});

it('renders the edit user page under strict authorization', function (): void {
    Filament::getPanel('admin')->strictAuthorization();

    $user = UserFactory::new()->createOne();

    livewire(EditUser::class, ['record' => $user->getKey()])
        ->assertOk();
});

it('renders the view user page under strict authorization', function (): void {
    Filament::getPanel('admin')->strictAuthorization();

    $user = UserFactory::new()->createOne();

    livewire(ViewUser::class, ['record' => $user->getKey()])
        ->assertOk();
});

it('globally searches user identifiers with roles inside the current tenant', function (): void {
    $tenant = currentTestTenant();
    $role = RoleFactory::new()->forTenant($tenant)->forGuard('web')->createOne([
        'name' => 'support-agent',
    ]);
    $user = UserFactory::new()->forTenant($tenant)->createOne([
        'username' => 'global-search-user',
        'email'    => 'global-search-user@example.test',
    ]);
    $user->assignRole($role);

    $otherTenant = createTestTenant();
    Filament::setTenant($otherTenant);
    switchToTestTenant($otherTenant);
    UserFactory::new()->createOne([
        'username' => 'other-tenant-user',
        'email'    => 'other-tenant-user@example.test',
    ]);
    Filament::setTenant($tenant);
    switchToTestTenant($tenant);

    $result = UserResource::getGlobalSearchResults('global-search-user@example.test')->sole();
    $loadedUser = UserResource::getGlobalSearchEloquentQuery()->findOrFail($user->getKey());

    expect(UserResource::getGloballySearchableAttributes())->toBe(['username', 'email'])
        ->and(UserResource::getGlobalSearchEloquentQuery()->pluck('username')->all())->toBe([
            'admin',
            'global-search-user',
        ])
        ->and($result->title)->toBe('global-search-user')
        ->and($result->details)->toBe([
            __('vendra-user::attributes.email')       => 'global-search-user@example.test',
            __('vendra-permission::navigation.role')  => 'support-agent',
        ])
        ->and($loadedUser->relationLoaded('roles'))->toBeTrue()
        ->and(UserResource::getGlobalSearchResults('other-tenant-user'))->toBeEmpty();
});

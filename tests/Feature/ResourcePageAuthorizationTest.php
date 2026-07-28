<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Misaf\VendraUser\Database\Factories\UserFactory;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\CreateUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\EditUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\ListUsers;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\ViewUser;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    setUpFilamentSuperAdminTestContext();
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

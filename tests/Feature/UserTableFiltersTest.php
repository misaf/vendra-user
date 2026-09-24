<?php

declare(strict_types=1);

use Misaf\VendraUser\Database\Factories\UserFactory;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\ListUsers;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    setUpFilamentAdminTestContext();
});

it('filters users by whether their email is verified', function (string $operator, bool $expectVerified): void {
    $verified = UserFactory::new()->createOne(['email_verified_at' => now()]);
    $unverified = UserFactory::new()->createOne(['email_verified_at' => null]);

    [$shown, $hidden] = $expectVerified ? [$verified, $unverified] : [$unverified, $verified];

    livewire(ListUsers::class)
        ->loadTable()
        ->filterTable('queryBuilder', ['rules' => [
            'rule' => ['type' => 'email_verified_at', 'data' => ['operator' => $operator, 'settings' => []]],
        ]])
        ->assertCanSeeTableRecords([$shown])
        ->assertCanNotSeeTableRecords([$hidden]);
})->with([
    'verified' => ['isFilled', true],
    'unverified' => ['isFilled.inverse', false],
]);

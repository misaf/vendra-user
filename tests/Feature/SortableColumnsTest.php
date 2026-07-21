<?php

declare(strict_types=1);

use Misaf\VendraUser\Database\Factories\UserFactory;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\ListUsers;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    setUpFilamentSuperAdminTestContext();
});

it('sorts the users table by every sortable column following the stored values', function (): void {
    $first = UserFactory::new()->createOne(['username' => 'aaa-user']);
    $second = UserFactory::new()->createOne(['username' => 'bbb-user']);

    expect(livewire(ListUsers::class)->call('loadTable'))
        ->toSortByEverySortableColumn([$first, $second]);
});

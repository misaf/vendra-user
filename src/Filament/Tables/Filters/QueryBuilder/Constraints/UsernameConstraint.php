<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Tables\Filters\QueryBuilder\Constraints;

use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;

final class UsernameConstraint extends TextConstraint
{
    public static function getDefaultName(): string
    {
        return 'username';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('vendra-user::attributes.username'));
    }
}

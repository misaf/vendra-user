<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Tables\Columns;

use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;

final class UsernameColumn extends TextColumn
{
    public static function getDefaultName(): string
    {
        return 'username';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('vendra-user::attributes.username'))
            ->icon(Heroicon::User)
            ->searchable();
    }
}

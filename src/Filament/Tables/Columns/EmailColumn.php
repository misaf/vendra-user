<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

final class EmailColumn extends TextColumn
{
    public static function getDefaultName(): string
    {
        return 'email';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('vendra-user::attributes.email'))
            ->searchable();
    }
}

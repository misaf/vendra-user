<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Tables\Columns;

use Misaf\VendraSupport\Filament\Tables\Columns\TimestampColumn;

final class EmailVerifiedAtColumn extends TimestampColumn
{
    public static function getDefaultName(): string
    {
        return 'email_verified_at';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('vendra-user::attributes.email_verified_at'))
            ->alignCenter()
            ->badge()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}

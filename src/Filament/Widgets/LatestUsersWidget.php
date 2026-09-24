<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Widgets;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Misaf\VendraSupport\Filament\Tables\Columns\CreatedAtColumn;
use Misaf\VendraUser\Filament\Tables\Columns\EmailColumn;
use Misaf\VendraUser\Filament\Tables\Columns\EmailVerifiedAtColumn;
use Misaf\VendraUser\Filament\Tables\Columns\UsernameColumn;
use Misaf\VendraUser\Models\User;

final class LatestUsersWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 1;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('vendra-user::navigation.recent_users'))
            ->query(User::query()->latest()->limit(5))
            ->columns([
                UsernameColumn::make(),

                EmailColumn::make(),

                EmailVerifiedAtColumn::make()
                    ->label(__('vendra-user::attributes.verified_at'))
                    ->toggleable(false),

                CreatedAtColumn::make()
                    ->alignCenter()
                    ->badge(),
            ])
            ->searchable(false)
            ->paginated(false);
    }
}

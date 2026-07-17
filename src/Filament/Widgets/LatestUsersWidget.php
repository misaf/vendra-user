<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
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
            ->heading(__('vendra-user::navigation.latest_users'))
            ->query(User::query()->latest()->limit(5))
            ->columns([
                TextColumn::make('username')
                    ->label(__('vendra-user::attributes.username')),

                TextColumn::make('email')
                    ->label(__('vendra-user::attributes.email'))
                    ->searchable(),

                TextColumn::make('email_verified_at')
                    ->alignCenter()
                    ->badge()
                    ->extraCellAttributes(['dir' => 'ltr'])
                    ->label(__('vendra-user::attributes.verified_at'))
                    ->sinceTooltip()
                    ->when(
                        app()->isLocale('fa'),
                        fn(TextColumn $column) => $column->jalaliDateTime('Y-m-d H:i', latinNumbers: true),
                        fn(TextColumn $column) => $column->dateTime('Y-m-d H:i')
                    ),

                TextColumn::make('created_at')
                    ->alignCenter()
                    ->badge()
                    ->extraCellAttributes(['dir' => 'ltr'])
                    ->label(__('vendra-user::attributes.created_at'))
                    ->sinceTooltip()
                    ->when(
                        app()->isLocale('fa'),
                        fn(TextColumn $column) => $column->jalaliDateTime('Y-m-d H:i', latinNumbers: true),
                        fn(TextColumn $column) => $column->dateTime('Y-m-d H:i')
                    ),
            ])
            ->searchable(false)
            ->paginated(false);
    }
}

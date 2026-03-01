<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\ViewUser;
use Misaf\VendraUser\Models\User;

final class LatestUserTableWidget extends BaseWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = [
        'sm' => 1,
        'lg' => 2,
    ];

    protected function getColumns(): int
    {
        return 1;
    }

    public static function isDiscovered(): bool
    {
        return true;
    }

    public static function canView(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('navigation.latest_users'))
            ->query(User::take(5))
            ->columns([
                TextColumn::make('username')
                    ->label(__('form.username')),
                // ->url(fn(User $record): string => ViewUser::getUrl(['record' => $record])),

                TextColumn::make('email')
                    ->label(__('form.email'))
                    ->searchable(),

                TextColumn::make('email_verified_at')
                    ->alignCenter()
                    ->badge()
                    ->extraCellAttributes(['dir' => 'ltr'])
                    ->label(__('form.verified_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sinceTooltip()
                    ->unless(app()->isLocale('fa'), fn(TextColumn $column) => $column->jalaliDate('Y-m-d', toLatin: true)),

                TextColumn::make('created_at')
                    ->alignCenter()
                    ->badge()
                    ->extraCellAttributes(['dir' => 'ltr'])
                    ->label(__('form.created_at'))
                    ->sinceTooltip()
                    ->dateTime('Y-m-d H:i')
                    ->unless(app()->isLocale('fa'), fn(TextColumn $column) => $column->jalaliDateTime('Y-m-d', toLatin: true)),
            ])
            ->searchable(false)
            ->paginated(false)
            ->poll('10s');
    }
}

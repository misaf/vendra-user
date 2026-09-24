<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieTagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Misaf\VendraSupport\Capabilities\TagIntegration;
use Misaf\VendraSupport\Filament\Tables\Columns\CreatedAtColumn;
use Misaf\VendraSupport\Filament\Tables\Columns\RowIndexColumn;
use Misaf\VendraSupport\Filament\Tables\Columns\UpdatedAtColumn;
use Misaf\VendraTagger\Filament\Tables\Columns\ModelTagsColumn;
use Misaf\VendraUser\Filament\Tables\Columns\EmailColumn;
use Misaf\VendraUser\Filament\Tables\Columns\EmailVerifiedAtColumn;
use Misaf\VendraUser\Filament\Tables\Columns\UsernameColumn;
use Misaf\VendraUser\Filament\Tables\Filters\QueryBuilder\Constraints\EmailConstraint;
use Misaf\VendraUser\Filament\Tables\Filters\QueryBuilder\Constraints\EmailVerifiedAtConstraint;
use Misaf\VendraUser\Filament\Tables\Filters\QueryBuilder\Constraints\UsernameConstraint;
use Misaf\VendraUser\Models\User;

final class UserTable
{
    public static function configure(Table $table): Table
    {
        /**
         * @var array<int, TextColumn|SpatieTagsColumn> $columns
         */
        $columns = [
            RowIndexColumn::make(),

            UsernameColumn::make(),

            EmailColumn::make(),

            TextColumn::make('roles.name')
                ->badge()
                ->label(__('vendra-permission::navigation.role'))
                ->separator(','),

            EmailVerifiedAtColumn::make(),

            CreatedAtColumn::make(),

            UpdatedAtColumn::make(),
        ];

        if (TagIntegration::isAvailable()) {
            $columns[] = ModelTagsColumn::make()
                ->type(User::TAG_TYPE);
        }

        return $table
            ->columns($columns)
            ->description(__('vendra-user::tables.description.users'))
            ->emptyStateHeading(__('vendra-user::tables.empty_state.heading.users'))
            ->emptyStateDescription(__('vendra-user::tables.empty_state.description.users'))
            ->emptyStateIcon(Heroicon::OutlinedUserGroup)
            ->filters(
                [
                    TrashedFilter::make(),
                    QueryBuilder::make()
                        ->constraints([
                            UsernameConstraint::make(),
                            EmailConstraint::make(),
                            EmailVerifiedAtConstraint::make(),
                        ]),
                ],
                layout: FiltersLayout::AboveContentCollapsible,
            )
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

                    EditAction::make(),

                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort(column: 'id', direction: 'desc');
    }
}

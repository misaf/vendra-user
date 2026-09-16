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
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Misaf\VendraSupport\Capabilities\TagIntegration;
use Misaf\VendraSupport\Filament\Tables\Columns\CreatedAtColumn;
use Misaf\VendraSupport\Filament\Tables\Columns\RowIndexColumn;
use Misaf\VendraSupport\Filament\Tables\Columns\UpdatedAtColumn;
use Misaf\VendraTagger\Filament\Tables\Columns\ModelTagsColumn;
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

            TextColumn::make('username')
                ->label(__('vendra-user::attributes.username'))
                ->icon(Heroicon::User)
                ->searchable(isGlobal: true),

            TextColumn::make('email')
                ->label(__('vendra-user::attributes.email'))
                ->icon(Heroicon::Envelope)
                ->searchable(isGlobal: true),

            TextColumn::make('roles.name')
                ->badge()
                ->label(__('vendra-permission::navigation.role'))
                ->separator(','),

            TextColumn::make('email_verified_at')
                ->alignCenter()
                ->badge()
                ->extraCellAttributes(['dir' => 'ltr'])
                ->label(__('vendra-user::attributes.email_verified_at'))
                ->sinceTooltip()
                ->toggleable(isToggledHiddenByDefault: true)
                ->when(
                    app()->isLocale('fa'),
                    fn (TextColumn $column) => $column->jalaliDateTime('Y-m-d H:i', latinNumbers: true),
                    fn (TextColumn $column) => $column->dateTime('Y-m-d H:i'),
                ),

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
                            TextConstraint::make('username')
                                ->label(__('vendra-user::attributes.username')),
                            TextConstraint::make('email')
                                ->label(__('vendra-user::attributes.email')),
                            BooleanConstraint::make('email_verified_at')
                                ->label(__('vendra-user::attributes.email_verified_at')),
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

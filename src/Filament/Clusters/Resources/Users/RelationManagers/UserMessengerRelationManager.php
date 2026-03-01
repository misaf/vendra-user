<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\RelationManagers;

use App\Tables\Columns\CreatedAtTextColumn;
use App\Tables\Columns\DeletedAtTextColumn;
use App\Tables\Columns\UpdatedAtTextColumn;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Misaf\VendraUserMessenger\Enums\UserMessengerPlatformEnum;

final class UserMessengerRelationManager extends RelationManager
{
    protected static string $relationship = 'userMessengers';

    public static function getModelLabel(): string
    {
        return __('user-messenger::navigation.user_messenger');
    }

    public static function getPluralModelLabel(): string
    {
        return __('user-messenger::navigation.user_messenger');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('user-messenger::navigation.user_messenger');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): string
    {
        return (string) Number::format($ownerRecord->userMessengers()->distinct('platform')->count());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('platform')
                ->columnSpanFull()
                ->label(__('user-messenger::attributes.platform'))
                ->native(false)
                ->options(UserMessengerPlatformEnum::class)
                ->required(),

            TextInput::make('key_name')
                ->label(__('transaction_metadata.key_name'))
                ->required(),

            TextInput::make('key_value')
                ->label(__('transaction_metadata.key_value'))
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('platform')
                    ->badge()
                    ->label(__('user-messenger::attributes.platform')),

                TextColumn::make('key_name')
                    ->alignStart()
                    ->label(__('transaction_metadata.key_name')),

                TextColumn::make('key_value')
                    ->alignStart()
                    ->copyable()
                    ->copyMessage(__('user::messages.value_copied'))
                    ->copyMessageDuration(1500)
                    ->label(__('transaction_metadata.key_value')),

                CreatedAtTextColumn::make('created_at'),

                UpdatedAtTextColumn::make('updated_at'),

                DeletedAtTextColumn::make('deleted_at'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

                    EditAction::make(),

                    DeleteAction::make(),
                ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = $this->getOwnerRecord()->getKey();

                        return $data;
                    }),
            ])
            ->groups([
                Group::make('key_name')
                    ->collapsible()
                    ->label(__('transaction_metadata.key_name')),

                Group::make('key_value')
                    ->collapsible()
                    ->label(__('transaction_metadata.key_value')),
            ]);
    }
}

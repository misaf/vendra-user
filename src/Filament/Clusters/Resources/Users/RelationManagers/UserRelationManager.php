<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\UserResource;
use Misaf\VendraUser\Models\User;

final class UserRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public static function getModelLabel(): string
    {
        return __('navigation.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.user');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('navigation.user');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): string
    {
        return (string) Number::format($ownerRecord->users()->count());
    }

    public function form(Schema $schema): Schema
    {
        return UserResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return UserResource::table($table)
            ->recordTitle(fn(User $record): string => "{$record->username} ({$record->email})")
            ->headerActions([
                CreateAction::make(),
                AttachAction::make()
                    ->recordSelectSearchColumns(['username', 'email'])
                    ->multiple(),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Number;
use Misaf\VendraSupport\Filament\Clusters\CustomersCluster;
use Misaf\VendraSupport\Filament\Navigation\NavigationPriority;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\CreateUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\EditUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\ListUsers;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\ViewUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Schemas\UserForm;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Tables\UserTable;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Widgets\UserOverviewWidget;

use Misaf\VendraUser\Models\User;

final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = NavigationPriority::Users->value;

    protected static ?string $recordTitleAttribute = 'username';

    protected static ?string $slug = 'users';

    /**
     * @var class-string<Cluster>|null
     */
    protected static ?string $cluster = CustomersCluster::class;

    public static function getBreadcrumb(): string
    {
        return __('vendra-user::navigation.user');
    }

    public static function getModelLabel(): string
    {
        return __('vendra-user::navigation.user');
    }

    public static function getNavigationLabel(): string
    {
        return __('vendra-user::navigation.users');
    }

    public static function getNavigationBadge(): string
    {
        return (string) Number::format(User::query()->count());
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return __('vendra-user::navigation.navigation_badge_tooltip');
    }

    public static function getPluralModelLabel(): string
    {
        return __('vendra-user::navigation.users');
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['username', 'email'];
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('vendra-user::attributes.email')      => $record->email,
            __('vendra-permission::navigation.role') => Arr::join(
                $record->roles()->pluck('name')->toArray(),
                ', ',
            ),
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view'   => ViewUser::route('/{record}'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<class-string<RelationManager>|RelationGroup|RelationManagerConfiguration>
     */
    public static function getRelations(): array
    {
        return [];
    }

    public static function getWidgets(): array
    {
        return [
            UserOverviewWidget::class,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserTable::configure($table);
    }
}

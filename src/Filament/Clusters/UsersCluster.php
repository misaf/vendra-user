<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use Misaf\VendraUser\UserPlugin;

final class UsersCluster extends Cluster
{
    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'users';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function getNavigationGroup(): string
    {
        return UserPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationLabel(): string
    {
        return __('vendra-user::navigation.user');
    }

    public static function getClusterBreadcrumb(): string
    {
        return UserPlugin::get()->getNavigationGroup();
    }
}

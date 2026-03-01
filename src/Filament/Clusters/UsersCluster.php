<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters;

use Filament\Clusters\Cluster;

final class UsersCluster extends Cluster
{
    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'users';

    public static function getNavigationGroup(): string
    {
        return __('navigation.user_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.user');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('navigation.user_management');
    }
}

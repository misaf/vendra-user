<?php

declare(strict_types=1);

namespace Misaf\VendraUser;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Misaf\VendraSupport\Filament\Concerns\HasPluginNavigationGroup;
use Misaf\VendraSupport\Filament\Concerns\ResolvesPluginInstances;

final class UserPlugin implements Plugin
{
    use HasPluginNavigationGroup;
    use ResolvesPluginInstances;

    public const string ID = 'vendra-user';

    public function getId(): string
    {
        return self::ID;
    }

    protected function defaultNavigationGroup(): string
    {
        return 'vendra-support::navigation.groups.Customers';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(
                in: __DIR__ . '/Filament/Clusters/Resources',
                for: 'Misaf\\VendraUser\\Filament\\Clusters\\Resources',
            )
            ->discoverWidgets(
                in: __DIR__ . '/Filament/Widgets',
                for: 'Misaf\\VendraUser\\Filament\\Widgets',
            );
    }

    public function boot(Panel $panel): void {}
}

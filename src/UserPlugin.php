<?php

declare(strict_types=1);

namespace Misaf\VendraUser;

use Filament\Contracts\Plugin;
use Filament\Panel;

final class UserPlugin implements Plugin
{
    public function getId(): string
    {
        return 'vendra-user';
    }

    public static function make(): static
    {
        /** @var static $plugin */
        $plugin = app(static::class);

        return $plugin;
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverClusters(
                in: __DIR__ . '/Filament/Clusters',
                for: 'Misaf\\VendraUser\\Filament\\Clusters',
            )
            ->discoverWidgets(
                in: __DIR__ . '/Filament/Widgets',
                for: 'Misaf\\VendraUser\\Filament\\Widgets',
            );
    }

    public function boot(Panel $panel): void {}
}

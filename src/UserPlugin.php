<?php

declare(strict_types=1);

namespace Misaf\VendraUser;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Config;

final class UserPlugin implements Plugin
{
    public const string ID = 'vendra-user';

    protected string|Closure|null $navigationGroup = null;

    public function getId(): string
    {
        return self::ID;
    }

    public static function make(): static
    {
        /** @var static $plugin */
        $plugin = app(self::class);

        return $plugin;
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(self::ID);

        return $plugin;
    }

    public function navigationGroup(string|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): string
    {
        $group = $this->navigationGroup ?? Config::get('vendra-user.navigation_group');

        if ($group instanceof Closure) {
            $group = $group();
        }

        if ( ! is_string($group) || '' === $group) {
            $group = 'vendra-support::navigation.groups.Customers';
        }

        return (string) __($group);
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

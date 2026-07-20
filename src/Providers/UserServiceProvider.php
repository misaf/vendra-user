<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Providers;

use Composer\InstalledVersions;

use Filament\Panel;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Misaf\VendraSupport\Filament\Concerns\ResolvesConfiguredPanels;
use Misaf\VendraSupport\Support\TenantSeeders;
use Misaf\VendraSupport\Support\TenantTableRegistry;
use Misaf\VendraUser\Console\Commands\AssignSuperAdminRoleCommand;
use Misaf\VendraUser\Console\Commands\CreateUserCommand;
use Misaf\VendraUser\Console\Commands\SeedCommand;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Services\UserService;
use Misaf\VendraUser\UserPlugin;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class UserServiceProvider extends PackageServiceProvider
{
    use ResolvesConfiguredPanels;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('vendra-user')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_users_table',
            ])
            ->hasCommands(
                AssignSuperAdminRoleCommand::class,
                CreateUserCommand::class,
                SeedCommand::class,
            )
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->askToStarRepoOnGitHub('misaf/vendra-user');
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(UserService::class);

        Panel::configureUsing(function (Panel $panel): void {
            if ( ! $this->shouldRegisterOnPanel($panel->getId(), 'vendra-user')) {
                return;
            }

            $panel->plugin(UserPlugin::make());
        });
    }

    public function packageBooted(): void
    {
        $this->app->make(TenantTableRegistry::class)->register('users');
        $this->app->make(TenantSeeders::class)->register('vendra-user:seed', priority: 20);

        AboutCommand::add('Vendra User', fn(): array => ['Version' => InstalledVersions::getPrettyVersion('misaf/vendra-user')]);

        Gate::after(function (User $user): ?true {
            return $user->hasRole(Config::string('vendra-permission.super_admin_role', 'superadmin')) ? true : null;
        });
    }
}

<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Providers;

use Composer\InstalledVersions;

use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Misaf\VendraSupport\Filament\Concerns\ResolvesConfiguredPanels;
use Misaf\VendraSupport\Tenancy\TenantSeeders;
use Misaf\VendraSupport\Tenancy\TenantTableRegistry;
use Misaf\VendraUser\Console\Commands\AssignAdminRoleCommand;
use Misaf\VendraUser\Console\Commands\CreateUserCommand;
use Misaf\VendraUser\Console\Commands\SeedCommand;
use Misaf\VendraUser\Models\User;
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
                AssignAdminRoleCommand::class,
                CreateUserCommand::class,
                SeedCommand::class,
            )
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->askToStarRepoOnGitHub('misaf/vendra-user');
            });
    }

    public function packageRegistered(): void
    {
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

        /*
         | Gate::after runs for whoever is checking, not only for this package's
         | User. The console and reseller panels authenticate their own
         | authenticatables, so a parameter typed `User` turns any ability check
         | they make into a TypeError rather than a denial. Widen the type and
         | let anyone who is not this package's user fall through.
         */
        Gate::after(function (Authenticatable $user): ?true {
            if ( ! $user instanceof User) {
                return null;
            }

            return $user->hasRole(Config::string('vendra-permission.admin_role')) ? true : null;
        });
    }
}

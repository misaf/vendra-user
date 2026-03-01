<?php

declare(strict_types=1);

namespace Misaf\VendraUser;

use Filament\Panel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Services\UserService;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class UserServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('vendra-user')
            ->hasConfigFile()
            ->hasTranslations()
            // ->hasMigrations([
            //     'add_tenant_id_column_to_roles_table',
            //     'add_tenant_id_column_to_permissions_table',
            //     'add_description_column_to_roles_table',
            //     'add_description_column_to_permissions_table',
            // ])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->askToStarRepoOnGitHub('misaf/vendra-user');
            });
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ('admin' !== $panel->getId()) {
                return;
            }

            $panel->plugin(UserPlugin::make());
        });

        $this->app->bind('user-service', fn(Application $app) => new UserService());
    }

    public function packageBooted(): void
    {
        AboutCommand::add('Vendra User', fn() => ['Version' => 'dev-master']);

        Gate::after(function (User $user): ?true {
            return $user->hasRole(Config::string('vendra-permission.super_admin_role', 'superadmin')) ? true : null;
        });

        // $this->discoverPackageFeatures();
        // $this->registerTenantFeatures();
    }
}

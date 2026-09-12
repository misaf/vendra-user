<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Providers;

use Composer\InstalledVersions;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Misaf\VendraSupport\Filament\Concerns\ResolvesConfiguredPanels;
use Misaf\VendraSupport\Tenancy\TenantSeeders;
use Misaf\VendraUser\Auth\PlatformUserProvider;
use Misaf\VendraUser\Console\Commands\AssignAdminRoleCommand;
use Misaf\VendraUser\Console\Commands\CreateUserCommand;
use Misaf\VendraUser\Console\Commands\SeedCommand;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\PanelAccessRegistry;
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
        $this->app->singleton(PanelAccessRegistry::class);

        Panel::configureUsing(function (Panel $panel): void {
            if (! $this->shouldRegisterOnPanel($panel->getId(), 'vendra-user')) {
                return;
            }

            $panel->plugin(UserPlugin::make());
        });
    }

    public function packageBooted(): void
    {
        /*
        | Platform guards (console, reseller) authenticate the same canonical
        | User but must never resolve a tenant row when emails collide. The
        | driver is registered here so the per-panel `auth.providers.console`
        | and `auth.providers.reseller` entries work wherever the
        | console/reseller packages point their guards at them.
        */
        Auth::provider('platform-eloquent', static fn ($app, array $config): PlatformUserProvider => new PlatformUserProvider($app['hash'], $config['model']));

        /*
        | `users` is deliberately absent from the TenantTableRegistry: a null
        | tenant id is a legitimate end state here (console users and
        | reseller users are platform-level identities), so the
        | `vendra-tenant:enable` retrofit must never backfill those rows or
        | force the column NOT NULL.
        */
        $this->app->make(TenantSeeders::class)->register('vendra-user:seed', priority: 20);

        AboutCommand::add('Vendra User', fn (): array => ['Version' => InstalledVersions::getPrettyVersion('misaf/vendra-user')]);

        /*
        | Gate::after runs for whoever is checking, not only for this package's
        | User. Every guard in this application authenticates the same canonical
        | User model, so the instanceof check below only guards against foreign
        | authenticatables (such as a host app's custom model); it is not a
        | panel discriminator. Keep the parameter typed to Authenticatable so
        | a check by any other authenticatable stays a denial, not a TypeError.
        */
        Gate::after(function (Authenticatable $user): ?true {
            if (! $user instanceof User) {
                return null;
            }

            return $user->hasRole(Config::string('vendra-permission.admin_role')) ? true : null;
        });
    }
}

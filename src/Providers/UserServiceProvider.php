<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Providers;

use Composer\InstalledVersions;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraSupport\Enums\PlanLimit;
use Misaf\VendraSupport\Filament\Concerns\ResolvesConfiguredPanels;
use Misaf\VendraSupport\Tenancy\TenantSeeders;
use Misaf\VendraSupport\Tenancy\TenantUsageRegistry;
use Misaf\VendraUser\Auth\TenantlessUserProvider;
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
            ->hasConsoleCommands(
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
        | Tenantless guards (console, reseller) authenticate the same canonical
        | User but must never resolve a tenant row when emails collide. The
        | driver is registered here so the per-panel `auth.providers.console`
        | and `auth.providers.reseller` entries work wherever the
        | console/reseller packages point their guards at them.
        */
        Auth::provider('tenantless-eloquent', static fn (Application $app, array $config): TenantlessUserProvider => new TenantlessUserProvider($app->make(Hasher::class), Arr::string($config, 'model')));

        /*
        | `users` is deliberately absent from the TenantTableRegistry: a null
        | tenant id is a legitimate end state here (console users and
        | reseller users are tenantless identities), so the
        | `vendra-tenant:enable` retrofit must never backfill those rows or
        | force the column NOT NULL.
        */
        $this->app->make(TenantSeeders::class)->register(SeedCommand::class, priority: 20);
        $this->app->make(TenantUsageRegistry::class)->register(
            PlanLimit::StaffPerStore,
            // Staff are the store's users who hold a role; customers hold none.
            fn (Model $tenant): int => User::query()
                ->withoutGlobalScopes()
                ->where(resolve(TenantResolver::class)->foreignKey(), $tenant->getKey())
                ->whereNull((new User)->getQualifiedDeletedAtColumn())
                ->whereHas('roles', fn (Builder $query): Builder => $query->withoutGlobalScopes())
                ->count(),
        );

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

# Vendra User

Tenant-aware user management for Vendra applications.

## Features

- User model, authentication fields, and tenant-aware storage
- Filament user administration on configured panels
- Role and permission integration through Spatie Permission
- User creation, admin assignment, and permission seeding commands
- Optional tags resolved through the shared Support capability contract
- Domain actions for tenant administrator membership and credential/account changes

## Requirements

- PHP 8.4+
- Laravel 13
- Filament 5
- `misaf/vendra-multimedia`
- `misaf/vendra-support`
- `spatie/laravel-permission`

Optional:

- `misaf/vendra-tagger` — enables assigning `user`-typed tags through `misaf/vendra-support`

## Installation

```bash
composer require misaf/vendra-user
php artisan vendor:publish --tag=vendra-user-migrations
php artisan migrate
```

Optionally publish configuration and translations:

```bash
php artisan vendor:publish --tag=vendra-user-config
php artisan vendor:publish --tag=vendra-user-translations
```

The service provider and Filament plugin are auto-registered. When an available
tenant resolver is bound, users are scoped and stamped automatically. If the
provider is installed after migrations have run, use
`php artisan vendra-tenant:enable {tenant}`.

Create users, assign the configured admin role, or seed module data with:

```bash
php artisan vendra-user:create
php artisan vendra-user:assign-admin
php artisan vendra-user:seed
```

## Administrator operations

Use `AddTenantAdministratorAction`, `PromoteTenantAdministratorAction`,
`DemoteTenantAdministratorAction`, `RemoveTenantAdministratorAction`, and
`SetUserAccountEnabledAction` for tenant membership changes. These actions lock
the tenant membership boundary and prevent the final enabled administrator from
being removed, demoted, or disabled. `UpdateUserEmailAction` and
`UpdateUserPasswordAction` provide normalized/validated credential changes,
framework hashing, and remember-token rotation without exposing stored hashes.

Forms and commands validate user credentials through `Support\UserRules`.
`username()` supplies the shared 3–12 character `alpha_dash` rules; the username
length constants also drive form inputs. `password()` uses the application
`Password::default()` policy, and `Support\PasswordGenerator::generate()` derives
its length and character set from that same policy, so seeded and command-issued passwords
pass the rules a supplied password must meet. Callers add required/optional, confirmation, and
scoped uniqueness rules; reseller registration additionally requires ASCII.
`UserRules::email()` is the strict email rule, and `UserRules::unique()` checks
a value within one tenant, or among tenantless users when the tenant is null;
`UserRules::exists()` is its counterpart, requiring a value some user in that same
scope holds. Soft-deleted users release their username and email, matching the
`users_active_username_unique` and `users_active_email_unique` indexes.

Look a tenantless identity up with the `User` model's `tenantless()` scope —
`User::query()->tenantless()->where('email', $email)->first()`. It drops the
tenant and team scopes and keeps `tenant_id` null, so a console or reseller
lookup never returns a tenant user who happens to share the email, whatever
tenant is current.

To find a user by an email, a username or both, chain the `identifiedBy()` scope:
`User::query()->tenantless()->identifiedBy($email, $username)->first()` returns the
user every given identifier names, or null when they name different users. It
throws without an identifier rather than matching every user.

## Optional tags

When Tagger is installed, the user form and table expose tags automatically. User imports neither Vendra Tagger nor Spatie Tags; the integration is resolved through Support.

Create tags with the reserved `user` type before assigning them:

```php
use Misaf\VendraTagger\Models\Tagger;

Tagger::findOrCreate('VIP', type: 'user', locale: 'en');
```

Demo seeders use bundled JSON fixtures in production and when their declared factory classes are unavailable. Local monorepo development continues to use factories when they are autoloadable.

## Testing

Run the package checks from the project root:

```bash
php artisan test --compact --testsuite=vendra-user
composer stan
```

## License

MIT. See [LICENSE](LICENSE).

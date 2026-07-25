# Vendra User

Tenant-aware user management for Vendra applications.

## Features

- User model, authentication fields, and tenant-aware storage
- Filament user administration on configured panels
- Role and permission integration through Spatie Permission
- User creation, super-admin assignment, and permission seeding commands
- Optional tags resolved through the shared Support capability contract

## Requirements

- PHP 8.3+
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

Create users, assign the configured super-admin role, or seed module data with:

```bash
php artisan user:create
php artisan user:assign-super-admin
php artisan vendra-user:seed
```

## Optional tags

When Tagger is installed, the user form and table expose tags automatically. User imports neither Vendra Tagger nor Spatie Tags; the integration is resolved through Support.

Create tags with the reserved `user` type before assigning them:

```php
use Misaf\VendraTagger\Models\Tagger;

Tagger::findOrCreate('VIP', type: 'user', locale: 'en');
```

## Testing

```bash
composer test
composer analyse
```

## License

MIT. See [LICENSE](LICENSE).

# Vendra User

Tenant-aware user management for Vendra applications.

## Requirements

- PHP 8.2+
- Laravel 12
- Filament 5
- Livewire 4
- Pest 4
- Tailwind CSS 4

Optional:

- `misaf/vendra-tagger` — enables assigning `user`-typed tags through `misaf/vendra-support`

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
```

## License

MIT. See [LICENSE](LICENSE).

---
name: vendra-user-development
description: "Use this skill when creating, modifying, reviewing, or testing the Vendra User module in app-modules/vendra-user, or when creating future user-like Filament/domain modules. Trigger for `User`, `SocialiteUser` models, vendra-user migrations, factories, seeders, policies, permission enums, Filament resources, clusters, forms, tables, relation managers, translations, media collections, plugin/service provider wiring, and module configuration."
---

# Vendra User

## Required Context

Always use this skill together with `modular` for module structure, `laravel-best-practices` for Laravel PHP, and `pest-testing` when tests are added or changed. Use `tailwindcss-development` only when editing Blade or Tailwind UI.

Before code changes, use Laravel Boost `application-info` and `search-docs` for the relevant packages. Prefer Boost database and browser tools over ad hoc debugging.

## Module Boundary

Treat `app-modules/vendra-user` as the source of user domain behavior and Filament admin UI.

- Use namespace `Misaf\VendraUser`.
- Keep domain models, factories, seeders, policies, observers, console commands, Filament classes, config, migrations, translations, and tests inside this module.
- Do not place user domain code in the host app unless the host app is only integrating the module.
- Keep cross-module dependencies explicit in `composer.json`; do not introduce a dependency without approval.

## Domain Model Standards

Follow the existing `User` and `SocialiteUser` patterns for new user entities.

- Use `declare(strict_types=1)`, final classes, typed method signatures, and PHPDoc generics for relationships.
- Follow Laravel comment style: document with PHPDoc (array shapes, generics, `@see`) and reserve inline comments for genuinely complex logic. Match the surrounding file's density and do not add comments that restate the code.
- Prefer the Laravel attributes already used here, such as `#[Fillable]`, `#[Hidden]`, `#[UseFactory]`, and `#[ObservedBy]`.
- This module is multi-tenancy core. The `User` model owns tenant membership (`HasTenants`, `teams()` / `tenants()`) and intentionally references the concrete tenant provider; keep that coupling deliberate and confined to membership concerns.
- For everything that is not tenant membership, still derive tenant awareness from the support layer (`TenantAwareness`, `BelongsToTenant`) and let `BelongsToTenant` assign `tenant_id`; do not spread `Misaf\VendraTenant` references into unrelated domain code.
- Use `HasTranslations` for localized `name`, `description`, and `slug`-like fields where the entity is translatable.
- Use `SoftDeletes` for user-managed content records unless there is a clear reason not to.
- Use `SortableTrait` and an integer `position` field for ordered admin content.
- For media-enabled records, implement `HasMedia`, use `InteractsWithMedia` with `HasDefaultMediaConversions`, expose a `multimedia()` morph relation, and define a stable `MEDIA_COLLECTION` constant.
- For slugs, use `Spatie\Sluggable\SlugOptions`, generate from translated names, and prevent overwrite unless regeneration is intended.

## Filament Standards

Keep Filament UI organized under `src/Filament/Clusters`.

- Register module UI through the module `Plugin` and `ServiceProvider`; do not manually wire resources in unrelated panel providers.
- Keep resource classes thin. Delegate form schemas to `Schemas/*Form.php` and table configuration to `Tables/*Table.php`.
- Use Filament v5 namespaces: form fields from `Filament\Forms\Components`, layout from `Filament\Schemas\Components`, table columns from `Filament\Tables\Columns`, filters from `Filament\Tables\Filters`, actions from `Filament\Actions`, and icons from `Filament\Support\Icons\Heroicon`.
- Use this module's translation keys (`vendra-user::attributes`, `vendra-user::navigation`) for labels, breadcrumbs, and navigation.
- Prevent N+1 issues in tables and relation managers with eager loading, `withCount`, or computed state based on loaded relationships.
- Use public media visibility only when public access is actually required.

## Permissions And Navigation

Use policy enums and policies as the permission source.

- Add enum cases for every resource action the panel exposes.
- Keep policy method names aligned with Filament actions: `viewAny`, `view`, `create`, `update`, `delete`, `deleteAny`, `restore`, `restoreAny`, `forceDelete`, `forceDeleteAny`, `replicate`, and `reorder` as applicable.
- Update `PermissionPolicySeeder` when new permissions are introduced.
- Keep navigation labels and groups configurable through the module `Plugin` and `config/vendra-user.php`. Do not add a `tenant_aware` config value; tenant awareness derives from the bound `TenantResolver`.

## Data And Localization

Migrations, factories, seeders, and translation files are part of the contract.

- Use package migrations in `database/migrations`, with stubs only when the install flow expects publishing.
- Use factories under `database/factories` and seeders under `database/seeders`. Keep them tenant-safe: import no concrete tenant provider and set no `tenant_id` directly; let `BelongsToTenant` assign it from the current tenant so they work with tenancy on or off.
- Keep demo fixtures deterministic and tenant-safe.
- Update all supported locales together and keep translation keys sorted.
- Preserve translation key parity tests when adding labels or attributes.

## Testing And Verification

Prefer focused Pest tests in the module.

- Keep tests purposeful and prevent unnecessary ones: cover behavior, contracts, and edge cases — not framework internals or trivially typed code. Do not duplicate coverage a focused test already proves, and do not add throwaway verification scripts (or `tinker`) when a test fits.
- Add or update unit tests for model contracts, policy permission coverage, resolver-derived tenant awareness, navigation/config behavior, and translation parity.
- Keep Pest architecture tests in `tests/ArchTest.php`: the `php`, `security`, and `laravel` presets, plus the standard presets (multi-tenancy core: no `not->toUse('Misaf\VendraTenant')` expectation).
- Add feature or Livewire tests when changing Filament behavior with meaningful user-visible effects.
- Run module checks from the package when possible: `composer --working-dir=app-modules/vendra-user test` and `composer --working-dir=app-modules/vendra-user analyse`.
- If PHP files changed, run Pint for the touched code: `vendor/bin/pint --dirty --format agent` from the host app, or the module formatter if working only inside the package.

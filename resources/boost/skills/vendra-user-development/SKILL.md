---
name: vendra-user-development
description: "Create, modify, review, or test the Vendra User package in packages/vendra-user. Use for User, authentication, tenant membership, media and optional tags, user actions/services/rules, migrations, factories, seeders, policies, Filament resources, configuration, translations, and package wiring. OAuth social login belongs to the separate vendra-socialite add-on."
---

# Vendra User

## Workflow

## Translatable Persistence

- Making a persisted model field translatable is an explicit domain choice unless this package already requires it.
- Every field listed in a model's `$translatable` array must definitely use a JSON database column. Keep its model traits/casts, factories, validation, Filament locale UI, API serialization, and tests translation-aware.
- A field not listed in `$translatable` must use the appropriate scalar database type and must not use Spatie Translatable, translatable slug traits, locale switchers, translated callbacks, or translation-shaped array data.

Always use this skill together with `laravel-best-practices` for Laravel PHP and `pest-testing` when tests are added or changed. Use `tailwindcss-development` only when editing Blade or Tailwind UI.

Before code changes, use Laravel Boost `application-info` and `search-docs` for the relevant packages. Prefer Boost database and browser tools over ad hoc debugging.

## Module Boundary

Treat `packages/vendra-user` as the source of user domain behavior and Filament admin UI.

- Use namespace `Misaf\VendraUser`.
- Keep domain models, factories, seeders, policies, observers, console commands, Filament classes, config, migrations, translations, and tests inside this module.
- Do not place user domain code in the host app unless the host app is only integrating the module.
- Keep cross-module dependencies explicit in `composer.json`; do not introduce a dependency without approval.
- Tag-consuming models must use `Misaf\VendraSupport\Traits\HasOptionalTags` as the single source of their `tags()` relationship and pivot metadata. Keep the package tag-agnostic: define a stable package-owned tag type, use `TagIntegration` for availability and UI integration, never import the concrete Vendra Tagger model/provider or define the relationship through Spatie `HasTags`, and list Tagger only under Composer `suggest`.

## Domain Model Standards

Follow the existing `User` model patterns for new user entities. Social login (the `SocialiteUser` model, `SocialiteRegistrar`, and filament-socialite wiring) lives in the separate `misaf/vendra-socialite` package; do not reintroduce it or a `dutchcodingcompany/filament-socialite` dependency here.

- Use `declare(strict_types=1)`, final classes, typed method signatures, and PHPDoc generics for relationships.
- Follow Laravel comment style: document with PHPDoc (array shapes, generics, `@see`) and reserve inline comments for genuinely complex logic. Match the surrounding file's density and do not add comments that restate the code.
- Prefer only the Laravel attributes already used by the affected sibling model; do not add model attributes merely because another package uses them.
- The `User` model owns Filament tenant membership (`HasTenants`, `teams()` / `tenants()`) but resolves the tenant model through support-layer `BelongsToTenant`. Never reference the concrete `Misaf\VendraTenant` provider.
- Derive all tenant awareness from the support layer (`TenantAwareness`, `BelongsToTenant`) and let the trait handle ordinary model creation.
- Reuse only the traits and conventions present on the affected sibling model; do not infer translations, media, slugs, sorting, or soft deletes from another package.

## Filament Standards

Keep every resource that declares a `$cluster`, including its complete supporting tree, under `src/Filament/Clusters/Resources/` with the matching `Misaf\VendraUser\Filament\Clusters\Resources` namespace and plugin discovery path. Resources without a cluster belong under `src/Filament/Resources/`.

- Register module UI through the module `Plugin` and `ServiceProvider`; do not manually wire resources in unrelated panel providers.
- Keep resource classes thin. Delegate form schemas to `Schemas/*Form.php` and table configuration to `Tables/*Table.php`.
- Use Filament v5 namespaces: form fields from `Filament\Forms\Components`, layout from `Filament\Schemas\Components`, table columns from `Filament\Tables\Columns`, filters from `Filament\Tables\Filters`, actions from `Filament\Actions`, and icons from `Filament\Support\Icons\Heroicon`.
- Use this module's translation keys (`vendra-user::attributes`, `vendra-user::navigation`) for labels, breadcrumbs, and navigation.
- Keep `UserResource` in `CustomersCluster` under the `vendra-user::navigation.user_management` navigation group. User-domain add-ons such as User Profile share this cluster and group.
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
- Keep architecture coverage proving the module does not use Vendra Tagger or Spatie Tags directly, and test the typed relationship through the Support resolver.
- Add or update unit tests for model contracts, policy permission coverage, resolver-derived tenant awareness, navigation/config behavior, and translation parity.
- Keep Pest architecture tests in `tests/ArchTest.php`: the `php`, `security`, and `laravel` presets, plus `arch()->expect('Misaf\VendraUser')->not->toUse('Misaf\VendraTenant')`.
- Add feature or Livewire tests when changing Filament behavior with meaningful user-visible effects.
- Run module checks from the package when possible: `composer --working-dir=packages/vendra-user test` and `composer --working-dir=packages/vendra-user analyse`.
- If PHP files changed, run Pint for the touched code: `vendor/bin/pint --dirty --format agent` from the host app, or the module formatter if working only inside the package.

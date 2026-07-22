## Vendra User

The `misaf/vendra-user` package owns user management, authentication, media handling, and multi-tenant membership and the Filament admin UI for users. Optional OAuth social login lives in the separate `misaf/vendra-socialite` add-on and must not be re-coupled here.

### Standards

### Translatable Persistence

- Making a persisted model field translatable is an explicit domain choice unless this package already requires it.
- Every field listed in a model's `$translatable` array must definitely use a JSON database column. Keep its model traits/casts, factories, validation, Filament locale UI, API serialization, and tests translation-aware.
- A field not listed in `$translatable` must use the appropriate scalar database type and must not use Spatie Translatable, translatable slug traits, locale switchers, translated callbacks, or translation-shaped array data.

- Register every table whose migration calls `TenantSchema::addTenantColumn()` with `TenantTableRegistry` in this package's service provider, preserving configured table names and connections, so `vendra-tenant:enable {tenant}` can retrofit schemas migrated before tenancy was enabled.

- Keep user domain code inside `packages/vendra-user` using the `Misaf\VendraUser` namespace.
- **Concrete User dependency policy.** Like `misaf/vendra-support`, this package is foundational infrastructure: modules whose domain is inherently about users (affiliate, permission, socialite, subscription, user-profile) may declare `misaf/vendra-user` in Composer `require` and import `Misaf\VendraUser\Models\User` directly — do not force a resolver abstraction on them. Peripheral or log-style modules (e.g. authify-log, developer-logins, activity/audit logging) must instead resolve the authenticatable model through `auth.providers.users.model` (see `Misaf\VendraTransaction\Support\TransactionUsers::model()` for the pattern), must not import `Misaf\VendraUser`, and should enforce that with an `arch()->expect(...)->not->toUse('Misaf\VendraUser')` test. Every module that imports the concrete `User` must declare `misaf/vendra-user` in its own `composer.json` — never rely on a transitive install.
- Use this package for models, migrations, factories, seeders, policies, permission enums, observers, Filament resources, translations, config, and package bootstrapping.
- Follow the concrete models and neighboring files in this package; do not apply translation, media, slug, sorting, or soft-delete patterns unless the affected model already uses them.
- Tenant awareness is owned by `misaf/vendra-support` via the bound `TenantResolver`; consume it through `Misaf\VendraSupport\Support\TenantAwareness` and `BelongsToTenant`, not a `tenant_aware` config toggle.
- The `User` model implements Filament tenant membership (`HasTenants`, `teams()` / `tenants()`) while resolving the tenant model through `BelongsToTenant` from `misaf/vendra-support`. Keep the module independent of concrete providers and never reference `Misaf\VendraTenant`.
- `User` carries a nullable `reseller_id` (a billing reseller owned by `misaf/vendra-subscription`, exposed via `isResellerOwner()`). `canAccessPanel()` gates the app's panels by this for `reseller` and by roles for `admin`/`user`. Console operators belong to the host application's separate authentication provider and must never be represented by this model. Keep `reseller_id` a plain nullable column with no relation here (no dependency on the subscription module).
- Keep Filament resources thin by delegating forms to `Schemas/*Form.php` and tables to `Tables/*Table.php`.
- Because `UserResource` declares a `$cluster`, keep its complete resource tree under `src/Filament/Clusters/Resources/`, use the matching `Misaf\VendraUser\Filament\Clusters\Resources` namespace, and keep plugin discovery aligned. Any future resource without a cluster must instead live under `src/Filament/Resources/`.
- Keep `UserResource` in `CustomersCluster`, ungrouped and ordered through `NavigationPriority`; optional user-domain resources such as User Profile use the same cluster with their own priority.
- Provide separate singular and plural resource labels in `en`, `de`, and `fa`: model labels use the singular key, while navigation and plural model labels use the plural key. Keep navigation labels at 24 characters or fewer.
- Follow Laravel comment style: document with PHPDoc (array shapes, generics, `@see`) and reserve inline comments for genuinely complex logic. Match the surrounding file and do not add comments that restate the code.
- Tag-consuming models must use `Misaf\VendraSupport\Traits\HasOptionalTags` as the single source of their `tags()` relationship and pivot metadata. Keep the package tag-agnostic: define a stable package-owned tag type, use `TagIntegration` for availability and UI integration, never import the concrete Vendra Tagger model/provider or define the relationship through Spatie `HasTags`, and keep Tagger in Composer `suggest` rather than `require`.
- Add or update Pest tests for policy coverage, config/navigation behavior, translation parity, model contracts, and user-visible Filament behavior.
- Keep tests purposeful and prevent unnecessary ones: cover behavior, contracts, and edge cases — not framework internals or trivially typed code. Do not duplicate coverage a focused test already proves, and do not add throwaway verification scripts when a test fits.
- Keep Pest architecture tests in `tests/ArchTest.php`: the `php`, `security`, and `laravel` presets plus `arch()->expect('Misaf\VendraUser')->not->toUse('Misaf\VendraTenant')`.
